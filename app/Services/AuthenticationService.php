<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuthenticationService
{
    // Session keys for context (association / counselor)
    public const S_ASSOC_ID    = 'ctx_assoc_id';
    public const S_ASSOC_NAME  = 'ctx_assoc_name';
    public const S_COUNSELOR   = 'ctx_counselor_email';

    // If your pivot table has a different name, change this.
    protected string $rolePivotTable = 'role_user';

    public function __construct(protected Guard $guard)
    {}

    /**
     * Entry point for link-based sign-in from Nexo.
     * $payload must include:
     *   - name
     *   - email
     *   - id (association id)
     *   - name (association name)
     *   - counselor (counselor name) [optional]
     *   - email_counselor (counselor email) [optional]
     *
     * This method:
     *  - finds or creates the student user
     *  - finds or creates the counselor user (if provided)
     *  - assigns roles:
     *      - student => owner role (scoped to assoc_id)
     *      - counselor => 'counselor' role (scoped to assoc_id)
     *  - ensures other users get default 'public' role when they first log in (see assignDefaultRole)
     */
    public function signInViaNexoLink(array $payload): ?User
    {
        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $assocId = $payload['id'] ?? null;
        $assocName = $payload['name'] ?? null;

        if ($email === '' || !$assocId || !$assocName) {
            // invalid link payload
            $this->audit(null, 'auth.link_login_failed', 'Missing required Nexo fields');
            return null;
        }

        // Create or find student user
        $student = $this->createOrFindUser($email, $payload['name'] ?? null);

        // Authenticate student in the app
        Auth::login($student, true);

        // Save association+ counselor in session for request context
        Session::put(self::S_ASSOC_ID, (string)$assocId);
        Session::put(self::S_ASSOC_NAME, (string)$assocName);

        if (!empty($payload['email_counselor'])) {
            Session::put(self::S_COUNSELOR, (string)$payload['email_counselor']);
        }

        // Assign roles:
        // 1) Student becomes owner for this association (scoped role)
        $this->assignRoleScoped($student, 'owner', 'association', $assocId);

        // 2) Counselor (if present) gets counselor role scoped to association
        if (!empty($payload['email_counselor'])) {
            $counselor = $this->createOrFindUser(strtolower(trim($payload['email_counselor'])), $payload['counselor'] ?? null);
            $this->assignRoleScoped($counselor, 'counselor', 'association', $assocId);
        }

        // 3) If the student also qualifies as venue manager / approver / deanship by other flags,
        //    make those assignments too (see methods below).
        $this->assignSpecialRolesIfApplicable($student);

        $this->audit($student, 'auth.link_login', "Nexo link login. assoc={$assocName} ({$assocId})");

        return $student;
    }

    /**
     * Create or find a user by email. If created, set a random password.
     */
    protected function createOrFindUser(string $email, ?string $fullName = null): User
    {
        $email = strtolower(trim($email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'email' => $email,
                // if you have name split fields, split fullName accordingly
                'name'  => $fullName ?? $email,
                // set a random password for local accounts created automatically
                'password' => bcrypt(Str::random(40)),
            ]);
            $this->audit($user, 'user.created_via_nexo', 'User created from Nexo redirect');
        }

        return $user;
    }

    /**
     * Assign a role by slug/name to a user with an optional scope.
     * Scope is a simple pair of (scope_type, scope_id) stored on the pivot if possible.
     * Examples:
     *   assignRoleScoped($u, 'owner', 'association', 123);
     *   assignRoleScoped($u, 'event_approver');
     */
    public function assignRoleScoped(User $user, string $roleSlug, ?string $scopeType = null, $scopeId = null): bool
    {
        /** @var Role|null $role */
        $role = Role::where('slug', $roleSlug)->orWhere('code', $roleSlug)->orWhere('name', $roleSlug)->first();

        if (!$role) {
            // Role not present in roles table; do not fail app flow, just audit and return false.
            $this->audit($user, 'role.assign_failed', "Role '{$roleSlug}' not found");
            return false;
        }

        // If the user model exposes a roles() many-to-many relation, use it (preferred).
        if (method_exists($user, 'roles')) {
            $pivotData = [];
            if ($scopeType !== null && $scopeId !== null) {
                $pivotData['scope_type'] = $scopeType;
                $pivotData['scope_id']   = (string)$scopeId;
            }
            // attach if not already attached for same scope
            $exists = $user->roles()
                ->wherePivot('role_id', $role->getKey())
                ->when($scopeType && $scopeId, fn($q) => $q->wherePivot('scope_type', $pivotData['scope_type'])->wherePivot('scope_id', $pivotData['scope_id']))
                ->exists();

            if (!$exists) {
                $user->roles()->attach($role->getKey(), $pivotData);
                $this->audit($user, 'role.assigned', "Assigned role {$role->name} (slug: {$roleSlug})" . ($scopeType ? " scoped {$scopeType}:{$scopeId}" : ''));
            }

            return true;
        }

        // Fallback: write directly to pivot table. Pivot columns expected: user_id, role_id, scope_type, scope_id.
        $payload = [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
        ];
        if ($scopeType !== null && $scopeId !== null) {
            $payload['scope_type'] = $scopeType;
            $payload['scope_id']   = (string)$scopeId;
        }

        // insert if not exists
        $existsQuery = DB::table($this->rolePivotTable)
            ->where('user_id', $payload['user_id'])
            ->where('role_id', $payload['role_id']);

        if (isset($payload['scope_type'])) $existsQuery->where('scope_type', $payload['scope_type']);
        if (isset($payload['scope_id']))   $existsQuery->where('scope_id', $payload['scope_id']);

        if (!$existsQuery->exists()) {
            DB::table($this->rolePivotTable)->insert($payload);
            $this->audit($user, 'role.assigned', "Assigned roleId={$payload['role_id']} for userId={$payload['user_id']}" . (isset($payload['scope_type']) ? " scoped {$payload['scope_type']}:{$payload['scope_id']}" : ''));
        }

        return true;
    }

    /**
     * Ensure every user has the default public role at least once.
     * Call during first sign-in if you want to guarantee baseline permissions.
     */
    public function assignDefaultPublicRole(User $user): void
    {
        $this->assignRoleScoped($user, 'public');
    }

    /**
     * If the user qualifies as venue manager, event approver, or deanship, assign appropriate roles.
     * These checks are intentionally generic — adapt to your real flags/relations.
     */
    protected function assignSpecialRolesIfApplicable(User $user): void
    {
        // Venue managers: if the user has a venues() relation, assign 'venue_manager' scoped to each venue id
        if (method_exists($user, 'venues')) {
            try {
                $venues = $user->venues()->pluck('id')->unique()->values()->all();
                foreach ($venues as $venueId) {
                    $this->assignRoleScoped($user, 'venue_manager', 'venue', $venueId);
                }
            } catch (\Throwable $e) {
                // swallow — relation may not exist or not be loaded
            }
        }

        // Event approver: common approach is a boolean or role in the upstream payload
        if (property_exists($user, 'is_event_approver') && $user->is_event_approver) {
            $this->assignRoleScoped($user, 'event_approver');
        } else {
            // fallback: check a column or method
            if (method_exists($user, 'isEventApprover') && $user->isEventApprover()) {
                $this->assignRoleScoped($user, 'event_approver');
            }
        }

        // Deanship / admin-of-some-kind: some systems mark a user as part of "Deanship of Admin"
        if (property_exists($user, 'is_deanship') && $user->is_deanship) {
            $this->assignRoleScoped($user, 'deanship');
            // deanship are also event approvers for qualifying events — still give them event_approver
            $this->assignRoleScoped($user, 'event_approver');
        } elseif (method_exists($user, 'isDeanship') && $user->isDeanship()) {
            $this->assignRoleScoped($user, 'deanship');
            $this->assignRoleScoped($user, 'event_approver');
        }

        // Ensure public role always exists
        $this->assignDefaultPublicRole($user);
    }

    /**
     * Remove scoped role for a user (if needed).
     */
    public function removeRoleScoped(User $user, string $roleSlug, ?string $scopeType = null, $scopeId = null): void
    {
        $role = Role::where('slug', $roleSlug)->orWhere('code', $roleSlug)->orWhere('name', $roleSlug)->first();
        if (!$role) return;

        if (method_exists($user, 'roles')) {
            $query = $user->roles()->wherePivot('role_id', $role->getKey());
            if ($scopeType && $scopeId) $query->wherePivot('scope_type', $scopeType)->wherePivot('scope_id', (string)$scopeId);
            $query->detach($role->getKey());
            $this->audit($user, 'role.removed', "Removed role {$roleSlug}" . ($scopeType ? " scoped {$scopeType}:{$scopeId}" : ''));
            return;
        }

        $q = DB::table($this->rolePivotTable)
            ->where('user_id', $user->getKey())
            ->where('role_id', $role->getKey());

        if ($scopeType && $scopeId) {
            $q->where('scope_type', $scopeType)->where('scope_id', (string)$scopeId);
        }

        $q->delete();
        $this->audit($user, 'role.removed', "Removed pivot role {$roleSlug} for user {$user->getKey()}");
    }

    /**
     * Central audit writer to match your AuditTrail model.
     * (columns: user_id, at_action, at_description, at_user)
     */
    protected function audit(?User $user, string $action, string $description = ''): void
    {
        try {
            AuditTrail::create([
                'user_id'        => $user?->getKey(),
                'at_action'      => $action,
                'at_description' => $description ?: null,
                'at_user'        => $user?->email,
            ]);
        } catch (\Throwable $e) {
            // swallow to avoid breaking auth flow; consider logging.
        }
    }
}
