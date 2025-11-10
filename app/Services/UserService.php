<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;

/**
 * UserService
 *
 * This service acts as the primary gateway for all business logic related to user entities.
 * It handles user creation, retrieval, and the management of internal roles.
 */
class UserService
{
    protected AuditService $auditService;
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Finds a user by their email or creates a new one if they don't exist.
     *
     * @param string $email The unique email address from the SSO provider.
     * @param string $name The full name of the user from the SSO provider.
     * @return User The found or newly created Eloquent User object.
     */
    public function findOrCreateUser(string $email, ?string $name = null): User
    {
        $user = User::firstOrCreate(
            ['email' => $email]
            //            ['first_name' => $name],
            ,
            [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => $email,
                'password' => bcrypt('password'),
                'auth_type' => 'saml2',
            ]
        );

        // Ensure the default 'user' role exists on the account
        try {
            // Try to find 'user' by code or name
            $default = Role::where('code', 'user')->orWhere('name', 'user')->first();
            if (!$default) {
                $default = Role::firstOrCreate(['code' => 'user'], ['name' => 'user']);
            }
            if ($default && method_exists($user, 'roles')) {
                $user->roles()->syncWithoutDetaching([(int) $default->id]);
            }
        } catch (\Throwable $e) {
            // noop: default role assignment best-effort
        }

        return $user;
    }

    /**
     * Retrieves a single user by their primary key.
     *
     * @param int $userId The primary key (user_id) of the user to find.
     * @return User|Error The Eloquent User object or Excemption if not found.
     */
    public function findUserById(int $userId): ?User
    {
        return User::findOrFail($userId);
    }

    /**
     * Retrieves all roles from the roles table.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Role[]
     */
    public function getAllRoles(): Collection
    {
        return Role::all();
    }

    /**
     * Retrieves a collection of all users who have no roles assigned.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithNoRoles(): Collection
    {
        return User::whereDoesntHave('roles')
            ->with('department')
            ->get();
    }

    // Removed implicit actor fallback helpers; admin actor must be provided explicitly by callers when auditing.

    /**
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user
     * @param array $roleCodes
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function updateUserRoles(User $user, array $roleCodes, User $admin, string $justification): User
    {
        // Normalize requested role identifiers to strings and ensure default 'user' role
        //        $existing = Role::whereIn('code', $roleCodes)->pluck('id', 'code');
        //        $missing  = array_values(array_diff($roleCodes, $existing->keys()->all()));

        $requested = collect($roleCodes)
            ->map(fn($v) => (string) $v)
            ->filter(fn($v) => $v !== '')
            ->values()
            ->all();
        if (!in_array('user', $requested, true)) {
            $requested[] = 'user'; // default role for all users
        }

        // Resolve existing roles by matching either code OR name (to handle legacy data)
        $roles = Role::query()
            ->whereIn('code', $requested)
            ->orWhereIn('name', $requested)
            ->get(['id', 'code', 'name']);

        $foundIds = $roles->pluck('id')->all();
        $foundKeys = $roles
            ->flatMap(function ($r) {
                return [$r->code => true, $r->name => true];
            })
            ->keys()
            ->all();

        // Create any missing roles using the provided string as the CODE; name is prettified
        $missing = array_values(array_diff($requested, $foundKeys));
        foreach ($missing as $rcode) {
            $created = Role::firstOrCreate(
                ['code' => $rcode],
                ['name' => \Illuminate\Support\Str::of($rcode)->replace('-', ' ')->title()]
            );
            //            $existing[$rcode] = $created->id;
            $foundIds[] = (int) $created->id;
        }

        // Sync the roles in the pivot table (dedup IDs)
        $user->roles()->sync(array_values(array_unique($foundIds)));

        // After $user->roles()->sync(...);

        // Require explicit admin for audit logging; no fallback
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            $ctx = ['meta' => ['roles' => array_values($roleCodes), 'source' => 'user_roles_update']];
            if (($justification = trim((string)$justification)) !== '') {
                $ctx['meta']['justification'] = $justification;
            }
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }

            $this->auditService->logAdminAction(
                (int) $admin->id,
                $actorName,
                'USER_ROLES_UPDATED',
                (string) ($user->id ?? 0),
                $ctx
            );
        }

        // Return the user with the fresh roles loaded
        return $user->load('roles');
    }

    /**
     * Assigns a staff member to a specific department.
     *
     * @param User $user
     * @param int $departmentId
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function assignUserToDepartment(User $user, int $departmentId, User $admin, string $justification): User
    {
        // Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        // Department PK is 'id'; assign to user's foreign key column
        $user->department_id = (int) $department->id;
        $user->save();

        // Audit the action; require explicit admin, no fallback
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            $deptName = (string) ($department->d_name ?? $department->name ?? '');
            $targetId = $deptName !== '' ? $deptName : (string)($department->id ?? 'department');

            $ctx = ['meta' => [
                'user_id'        => (int) ($user->id ?? 0),
                'user_email'     => (string) ($user->email ?? ''),
                'department_id'  => (int) ($department->id ?? 0),
                'department_name' => $deptName,
                'source'         => 'user_dept_assign',
            ]];
            if (($justification = trim((string)$justification)) !== '') {
                $ctx['meta']['justification'] = $justification;
            }
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }

            $this->auditService->logAdminAction(
                (int) $admin->id,
                $actorName,
                'USER_DEPT_ASSIGNED',
                $targetId,
                $ctx
            );
        }
        return $user;
    }

    /**
     * Updates a user's profile information.

     * @param User $user
     * @param array $data
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function updateUserProfile(User $user, array $data, User $admin, string $justification): User
    {
        // Define a whitelist of fields that are allowed to be updated to prevent mass assignment vulnerabilities.
        $fillableData = Arr::only($data, ['first_name', 'last_name', 'email']);

        $user->fill($fillableData);
        $user->save();

        if ($admin && $admin->id) {
            $adminName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($adminName === '') {
                $adminName = (string)($admin->email ?? '');
            }
            $ctx = ['meta' => [
                'fields' => array_keys($fillableData),
                'source' => 'user_profile_update',
            ]];
            if (($justification = trim((string)$justification)) !== '') {
                $ctx['meta']['justification'] = $justification;
            }
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }
            $this->auditService->logAdminAction(
                (int) $admin->id,
                $adminName,
                'USER_PROFILE_UPDATED',
                (string) ($user->id ?? 0),
                $ctx
            );
        }

        return $user;
    }

    /**
     * Create a new user with the provided data.
     * 
     * @param array $data
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function createUser(array $data, User $admin): User
    {
        $payload = [
            'first_name' => $data['first_name'] ?? '',
            'last_name'  => $data['last_name']  ?? '',
            'email'      => $data['email'],
            'auth_type'  => $data['auth_type'] ?? 'saml',
        ];
        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user = User::create($payload);

        // Ensure the default 'user' role is attached on creation
        try {
            $default = Role::where('code', 'user')->orWhere('name', 'user')->first();
            if (!$default) {
                $default = Role::firstOrCreate(['code' => 'user'], ['name' => 'user']);
            }
            if ($default && method_exists($user, 'roles')) {
                $user->roles()->syncWithoutDetaching([(int) $default->id]);
            }
        } catch (\Throwable $e) {
            // best-effort; continue
        }

        // Only log if explicit admin is provided
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            // Optional HTTP context (falls back to meta-only if no request)
            $ctx = ['meta' => ['source' => 'user_create']];
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) {
                // keep $ctx as-is
            }

            $this->auditService->logAdminAction(
                (int) $admin->id,
                $actorName,                 // target_type (keeps your existing pattern)
                'USER_CREATED',             // action
                (string) ($user->id ?? 0),  // target_id
                $ctx
            );
        }

        return $user;
    }

    /**
     * Retrieves a collection of all users who have a specific role.
     *
     * @param string $roleCode The machine-readable code for the role (e.g., 'dsca-staff').
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithRole(string $roleCode): Collection
    {
        return User::whereHas('roles', function ($query) use ($roleCode) {
            // Align with roles table schema: column is 'code' (not 'r_code')
            $query->where('code', $roleCode);
        })
            ->with(['department', 'roles'])
            ->get();
    }
}
