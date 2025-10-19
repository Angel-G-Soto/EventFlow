<?php
namespace App\Services;

use App\Models\Department;
use App\Models\User;
use \Illuminate\Database\Eloquent\Collection;

class DepartmentService {

    protected AuditService $auditService;

    /**
     * Inject the AuditService for logging administrative actions.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Retrieves all departments. Useful for populating dropdowns.
     */
    public function getAllDepartments(): Collection
    {
        return Department::orderBy('d_name')->get();
    }
    
    /**
     * Retrieves a single department by its primary key.
     * 
     * @param int $departmentId The primary key (department_id) of the Department to find.
     * @return Department|Error The Eloquent Department object or Excemption if not found.
     */
    public function getDepartmentById(int $departmentId): ?Department
    {
        return Department::find($departmentId);
    }

    /**
     * Creates a new department record.
     * 
     * @param string $name Name of department to be created
     * @param User $admin The admnistrator performing the action.
     * @return Department The newly created Eloquent Department object 
     */
    public function createDepartment(string $name, User $admin): Department
    {   
        // 1. Create department based on data value.
        $department = Department::create(['d_name' => $name]);

        // 2. Audit the action
        $description = "Created new department '{$department->d_name}' with ID: '{$department->department_id}'";
        $actionCode =  'DEPARTMENT_CREATED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 3. Return created Department object
        return $department;
    }

    /**
     * Updates an existing department's details.
     *
     * @param Department Department that is being updated.
     * @param string $name The updatable field.
     * @param User $admin The administrator performing the action.
     */
    public function updateDepartment(Department $department, string $name, User $admin): Department
    {
        // 1. Update Department attributes to data fields
        $department->update(['d_name' => $name]);

        // 2. Audit the action
        $description = "Updated department name to '{$department->d_name} code updated to '{$department->d_code}' (ID: {$department->department_id}).";
        $actionCode =  'DEPARTMENT_UPDATED';

        // 3. Audit the action 
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);
        
        // 4. Return the updated Department object
        return $department;
    }

    /**
     * Deletes a department and handles its relationships.
     *
     * @param Department $department The department to delete.
     * @param User       $admin      The administrator performing the action.
     * @return void
     */
    public function deleteDepartment(Department $department, User $admin): void
    {
        // 1. Get details before deleting
        $departmentName = $department->d_name; 
        $departmentId = $department->department_id;

        // 2. Delete Department
        $department->delete();
        
        // 3. Audit the action
        $description = "Delted department '{$departmentName} (ID: {$departmentId}).";
        $actionCode =  'DEPARTMENT_DELETED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);
    }

   /**
     * Retrieves all venues associated with a specific department.
     * 
     * @param Department $department Target Department to retrive assigned Venues.
     * @return Collection Collection of Venue objects assosiated to the Department. 
     */
    public function getDepartmentVenues(Department $department): Collection
    {
        return $department->venues;
    }

    /**
     * Retrieves all users assigned to a specific department.
     * 
     * @param Department $department Target Department to retrive assigned Users.
     * @return Collection Collection of Users objects assosiated to the Department. 
     */
    public function getDepartmentUsers(Department $department): Collection
    {
        return $department->users;
    }

}
