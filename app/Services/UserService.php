<?php
namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;

/**
 * UserService
 *
 * This service acts as the primary gateway for all business logic related to user entities.
 * It handles user creation, retrieval, and the management of internal roles.
 */
class UserService
{   
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
            ['u_email' => $email],
            ['u_name' => $name], 
        );
    }

    /**
     * Retrieves a single user by their primary key.
     *
     * @param int $userId The primary key (user_id) of the user to find.
     * @return User|null The Eloquent User object or null if not found.
     */
    public function findUserById(int $userId): ?User
    {
       return User::find($userId);
    }

    /**
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user The user account whose roles are being modified.
     * @param array $roleCodes A simple array of role codes (e.g., ['space-manager']).
     * @param int $admin_id The id of the administrator performing the action.
     * @return User The updated User object.
     */
    public function updateUserRoles(User $user, array $roleCodes, int $admin_id): User
    {
        // Find the Role model IDs corresponding to the codes
        $roleIds = Role::whereIn('r_code', $roleCodes)->pluck('role_id');

        // Sync the roles in the pivot table
        $user->roles()->sync($roleIds);

        // Audit the action
        $this->auditService->logAdminAction(
            $admin_id,
            'USER_ROLES_UPDATED',
            "Updated roles for user '{$user->u_name}' (ID: {$user->user_id}) to: " . implode(', ', $roleCodes)
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
    public function assignUserToDepartment(User $user, int $departmentId, int $admin_id): User
    {
        // Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        $user->department_id = $department->department_id;
        $user->save();

        // Audit the action
        $this->auditService->logAdminAction(
            $admin_id,
            'USER_DEPT_ASSIGNED',
            "Assigned user '{$user->u_name}' to department '{$department->d_name}'."
        );

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
            $query->where('r_code', $roleCode);
        })->get();
    }
}
