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
    public function findOrCreateUser(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['first_name' => $name],
        );
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
     * Retrieves a collection of all users who have no roles assigned.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithNoRoles(): Collection
    {
        return User::whereDoesntHave('roles')->get();
    }

    /**
     * Retrieve the first available user in the system, or null if none exist.
     */
    public function getFirstUser(): ?User
    {
        return User::query()->first();
    }

    /**
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user The user account whose roles are being modified.
     * @param array $roleCodes A simple array of role codes (e.g., ['space-manager']).
     * @param int $admin_id The id of the administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserRoles(User $user, array $roleCodes, ?User $admin = null): User
    {
        // Ensure roles exist for provided codes, creating any missing
        $existing = Role::whereIn('code', $roleCodes)->pluck('id', 'code');
        $missing  = array_values(array_diff($roleCodes, $existing->keys()->all()));
        foreach ($missing as $rcode) {
            $created = Role::firstOrCreate(
                ['code' => $rcode],
                ['name' => \Illuminate\Support\Str::of($rcode)->replace('-', ' ')->title()]
            );
            $existing[$rcode] = $created->id;
        }

        // Sync the roles in the pivot table
        $user->roles()->sync(array_values($existing->all()));

        // After $user->roles()->sync(...);

        $actor = $admin ?: $this->getFirstUser();
        if ($actor && $actor->id) {
            $actorName = trim(((string)($actor->first_name ?? '')) . ' ' . ((string)($actor->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($actor->email ?? '');
            }

            $ctx = ['meta' => ['roles' => array_values($roleCodes), 'source' => 'user_roles_update']];
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), ['roles' => array_values($roleCodes), 'source' => 'user_roles_update']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }

            $this->auditService->logAdminAction(
                (int) $actor->id,
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
     * @param User $user The user account to be assigned.
     * @param int $departmentId The primary key (d_id) of the department.
     * @param int $admin_id The id of the administrator performing the action.
     * @return User The updated User object.
     */
    public function assignUserToDepartment(User $user, int $departmentId, ?User $admin = null): User
    {
        // Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        $user->department_id = $department->department_id;
        $user->save();

        // Audit the action
        $actor = $admin ?: $this->getFirstUser();
        if ($actor && $actor->id) {
            $actorName = trim(((string)($actor->first_name ?? '')) . ' ' . ((string)($actor->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($actor->email ?? '');
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
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }

            $this->auditService->logAdminAction(
                (int) $actor->id,
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
     *
     * @param int $userId The ID of the user to update.
     * @param array $data An associative array of data to update (e.g., ['first_name' => 'New Name']).
     * @param int $adminId The ID of the administrator performing the action.
     * @param string $adminName The name of the administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserProfile(User $user, array $data, ?User $admin = null): User
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
            $this->auditService->logAdminAction(
                (int) $admin->id,
                $adminName,
                'USER_PROFILE_UPDATED',
                (string) ($user->id ?? 0)
            );
        }

        return $user;
    }

    /**
     * Create a new user with the provided data.
     * Expected keys: first_name, last_name, email, auth_type, password (optional)
     */
    public function createUser(array $data, ?User $admin = null): User
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
        // In UserService::createUser(array $data, ?User $admin = null)

        $user = User::create($payload);

        // Always attempt to log (fallback to a safe actor if $admin is null)
        $actor = $admin;
        if (!$actor) {
            try {
                $actor = $this->getFirstUser(); // returns first user or null
            } catch (\Throwable) {
                $actor = null;
            }
        }

        if ($actor && $actor->id) {
            $actorName = trim(((string)($actor->first_name ?? '')) . ' ' . ((string)($actor->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($actor->email ?? '');
            }

            // Optional HTTP context (falls back to meta-only if no request)
            $ctx = ['meta' => ['source' => 'user_create']];
            try {
                if (request()) {
                    $ctx = app(\App\Services\AuditService::class)
                        ->buildContextFromRequest(request(), ['source' => 'user_create']);
                }
            } catch (\Throwable) {
                // keep $ctx as-is
            }

            $this->auditService->logAdminAction(
                (int) $actor->id,
                $actorName,                 // target_type (keeps your existing pattern)
                'USER_CREATED',             // action
                (string) ($user->id ?? 0),  // target_id
                $ctx
            );
        }

        return $user;
    }

    /**
     * Permanently deletes a user account.
     *
     * @param int $userId The ID of the user to delete.
     * @param int $adminId The ID of the administrator performing the action.
     * @param string $adminName The name of the administrator performing the action.
     * @return void
     */
    public function deleteUser(User $user, ?User $admin = null): void
    {
        // Capture details before delete for auditing
        $deletedUserName = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? ''));
        $deletedUserEmail = (string) $user->email;
        $deletedUserId = (int) ($user->id ?? 0);

        $user->delete();

        if ($admin && $admin->id) {
            $adminName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($adminName === '') {
                $adminName = (string)($admin->email ?? '');
            }
            $this->auditService->logAdminAction(
                (int) $admin->id,
                $adminName,
                'USER_DELETED',
                (string) $deletedUserId,
                ['meta' => ['email' => $deletedUserEmail, 'name' => $deletedUserName]]
            );
        }
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
        })->get();
    }
}
