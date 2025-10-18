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
     */
    public function getDepartmentById(int $departmentId): ?Department
    {
        return Department::find($departmentId);
    }

    /**
     * Creates a new department record.
     * The Department data must be structured as such
     * '$data' => [
     *        'd_name' => string,
     *        'd_code' => string
     *  ] 
     */
    public function createDepartment(array $data, User $admin): Department
    {   
        // 1. Create department based on data values.
        $department = Department::create([
            'd_name' => $data['d_name'],
            'd_code' => $data['d_code']
        ]);

        // 2. Audit the action
        $description = "Created new department '{$department->d_name}' with ID: '{$department->department_id}'";
        $actionCode =  'DEPARTMENT_CREATED';
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 3. Return created Department object
        return $department;
    }

    /**
     * Updates an existing department's details.
     * The Department data must be structured as such
     * '$data' => [
     *        'd_name' => string,
     *        'd_code' => string
     *  ] 
     */
    public function updateDepartment(Department $department, array $data, User $editor): Department
    {
        // 1. Update Department attributes to data fields
        $department->update([
            'd_name' => $data['d_name'],
            'd_code' => $data['d_code']
        ]);

         // 2. Audit the action
        $description = "Updated department name to '{$department->d_name} code updated to '{$department->d_code}' (ID: {$department->department_id}).";
        $actionCode =  'DEPARTMENT_UPDATED';

        if($editor->hasRole('system-admin')){
            $this->auditService->logAdminAction($editor->user_id, $editor->u_name, $actionCode, $description);
        } else{ 
            $this->auditService->logAction($editor->user_id, $editor->u_name, $actionCode, $description);
        }

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
     */
    public function getDepartmentVenues(Department $department): Collection
    {
        // The 'venues' relationship should be defined on the Department model.
        return $department->venues;
    }

    /**
     * Retrieves all users assigned to a specific department.
     */
    public function getDepartmentUsers(Department $department): Collection
    {
        // The 'users' relationship should be defined on the Department model.
        return $department->users;
    }

}
