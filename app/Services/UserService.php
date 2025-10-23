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
{   private AuditService $auditService;
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
        $user = User::firstOrCreate(
            ['u_email' => $email],
            ['u_name' => $name],
        );

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
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user The user account whose roles are being modified.
     * @param array $roleCodes A simple array of role codes (e.g., ['space-manager']).
     * @param User $admin The administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserRoles(User $user, array $roleCodes, User $admin): User
    {
        // 1. Find the Role model IDs corresponding to the codes
        $roleIds = Role::whereIn('r_code', $roleCodes)->pluck('role_id');

        // 2. Sync the roles in the pivot table
        $user->roles()->sync($roleIds);

        // 3. Audit the action
        $description =  "Updated roles for user '{$user->u_name}' (ID: {$user->user_id}) to: " . implode(', ', $roleCodes);
        $actionCode = 'USER_ROLES_UPDATED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 4. Return the user with the fresh roles loaded
        return $user->load('roles');
    }

    /**
     * Assigns a staff member to a specific department.
     *
     * @param User $user The user account to be assigned.
     * @param int $departmentId The primary key (department_id) of the department.
     * @param User $admin The the administrator performing the action.
     * @return User The updated User object.
     */
    public function assignUserToDepartment(User $user, int $departmentId, User $admin): User
    {
        // 1. Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        // 2. Assign the department to the user
        $user->department_id = $department->department_id;
        $user->save();

        // 3. Audit the action
        $description = "Assigned user '{$user->u_name}' to department '{$department->d_name}'.";
        $actionCode = 'USER_DEPT_ASSIGNED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 4. Return updated User object
        return $user;
    }

    /**
     * Updates a user's profile information.
     *
     * @param User $user The the user to update.
     * @param array $data An associative array of data to update (e.g., ['u_name' => 'New Name']).
     * @param User $admin The the administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserProfile(User $user, array $data, User $admin): User
    {
        // 1. Define a whitelist of fields that are allowed to be updated to prevent mass assignment vulnerabilities.
        $fillableData = Arr::only($data, ['u_name', 'u_email', 'u_is_active']);
        $user->fill($fillableData);
        $user->save();

        // 2. Audit the action
        $description = "Updated profile for user '{$user->u_name}' (ID: {$user->user_id}).";
        $actionCode = 'USER_PROFILE_UPDATED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 3. Return updated user object
        return $user;
    }

    /**
     * Permanently deletes a user account.
     *
     * @param User $user The the user to delete.
     * @param User $admin The administrator performing the action.
     * @return void
     */
    public function deleteUser(User $user, User $admin): void
    {
        // 1. Get the user's name *before* deleting them for the audit log.
        $deletedUserName = $user->u_name;
        $deletedUserEmail = $user->u_email;
        $deletedUserId = $user->user_id;

        // 2. Delete the user
        $user->delete();

        // 3. Audit the action
        $description = "Permanently deleted user '{$deletedUserName}' (Email: {$deletedUserEmail}) (ID: {$deletedUserId}).";
        $actionCode =  'USER_DELETED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);
    }

     /**
     * Retrieves a collection of all requests who have a specific role.
     *
     * @param string $roleIdentifier The idenfier for the role (e.g., 'dsca-staff' || DSCA Staff).
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithRole(string $roleIdentifier): Collection
    {
        return User::whereHas('roles', function ($query) use ($roleIdentifier) {
            $query->where('r_code', $roleIdentifier)->orWhere('r_name', $roleIdentifier);
        })->get();
    }
}
