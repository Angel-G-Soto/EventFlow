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

//        $user->roles()->attach(
//            Role::where('name', 'venue-manager')->first()->id
//        );


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
     * @param int $admin_id The id of the administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserRoles(User $user, array $roleCodes, User $admin): User
    {
        // Find the Role model IDs corresponding to the codes
        $roleIds = Role::whereIn('r_code', $roleCodes)->pluck('role_id');

        // Sync the roles in the pivot table
        $user->roles()->sync($roleIds);

        // Audit the action
        $this->auditService->logAdminAction(
            $admin->user_id,
            $admin->name,
            'USER_ROLES_UPDATED',
            "Updated roles for user '{$user->name}' (ID: {$user->user_id}) to: " . implode(', ', $roleCodes)
        );

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
    public function assignUserToDepartment(User $user, int $departmentId, User $admin): User
    {
        // Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        $user->department_id = $department->department_id;
        $user->save();

        // Audit the action
        $this->auditService->logAdminAction(
            $admin->user_id,
            $admin->name,
            'USER_DEPT_ASSIGNED',
            "Assigned user '{$user->name}' to department '{$department->d_name}'."
        );

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
    public function updateUserProfile(User $user, array $data, User $admin): User
    {
        // Define a whitelist of fields that are allowed to be updated to prevent mass assignment vulnerabilities.
        $fillableData = Arr::only($data, ['first_name', 'email']);

        $user->fill($fillableData);
        $user->save();

        $this->auditService->logAdminAction(
            $admin->user_id,
            $admin->name,
            'USER_PROFILE_UPDATED',
            "Updated profile for user '{$user->name}' (ID: {$user->user_id})."
        );

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
    public function deleteUser(User $user, User $admin): void
    {
        // It's important to get the user's name *before* deleting them for the audit log.
        $deletedUserName = $user->name;
        $deletedUserEmail = $user->email;
        $deletedUserId = $user->user_id;

        $user->delete();

        $this->auditService->logAdminAction(
            $admin->user_id,
            $admin->name,
            'USER_DELETED',
            "Permanently deleted user '{$deletedUserName}' (Email: {$deletedUserEmail}) (ID: {$deletedUserId})."
        );
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
            $query->where('r_code', $roleCode);
        })->get();
    }
}
