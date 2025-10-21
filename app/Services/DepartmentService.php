<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;

class DepartmentService {

    /**
     * Returns the department that has the provided id
     *
     * @param int $id
     * @return Department
     * @throws Exception
     */
    public static function getDepartmentByID(int $id): Department
    {
        try {
            if ($id < 0 || $id == null) throw new \InvalidArgumentException();

            return Department::find($id);
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('We were not able to find a department with that ID.');}
    }

    /**
     * Returns a collection of all the available departments
     *
     * @return Collection
     * @throws Exception
     */
    public static function getAllDepartments(): Collection
    {
        try {
            return Department::all();
        }
        catch (Throwable $exception) {throw new Exception('We were not able to find a department with that ID.');}
    }

    /**
     * Updates or creates the departments provided within an array.
     * The array must be structured as follows:
     *
     * [
     *      [
     *          d_name => 'name_of_department'
     *          d_code => 'code_of_department'
     *      ],
     *      ...
     * ]
     *
     * @param array $departmentData
     * @return mixed
     * @throws Exception
     */
    public static function updateOrCreateDepartment(array $departmentData): Collection
    {
        try {
            // Iterate through the array
            $updatedDepartments = new Collection();
            foreach ($departmentData as $department) {

                // Find value based on the name and code. Update its fields
                $updatedDepartments->add(Department::updateOrCreate(
                    [
                        'd_name' => $department['d_name'],
                        'd_code' => $department['d_code'],
                    ],
                    [
                        'd_name' => $department['d_name'],
                        'd_code' => $department['d_code'],
                    ]
                ));
            }

            // Add audit trail

            // Return collection of updated values
            return $updatedDepartments;
        }
        catch (Throwable $exception) {throw new Exception('Unable to synchronize department data.');}
    }

    /**
     * Deletes the department that contains the specified id
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function deleteDepartment(int $id): bool
    {
        try {
            if ($id < 0 || $id == null) throw new InvalidArgumentException();

            return Department::find($id)->delete();
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('We were not able to delete the specified department.');}
    }

    /**
     * Returns the use requirements of the specified department
     *
     * @param int $id
     * @return Collection
     * @throws Exception
     */
    public static function getUseRequirement(int $id): Collection
    {
        {
            try {
                if ($id == 0 || $id == null) {throw new InvalidArgumentException();}
                $department = Department::find($id);
                if ($department == null) {throw new ModelNotFoundException();}
                return $department->requirements;
            }
            catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
            catch (Throwable $exception) {throw new Exception('We were not able to find the requirements for the department.');}
        }
    }


//    public static function updateDepartmentAssignment(Department $department, Venue $venue): void
//    {
//        $venue->department_id = $department->id;
//        $venue->save();
//    }
//
    public static function updateUserDepartment(Department $department, User $manager): void
    {
        $manager->department_id = $department->id;
        $manager->save();
    }
//
//    public static function getDepartmentVenues(Department $department): Collection
//    {
//        return Department::with('venues')->where('id', $department->id)->get();
//    }
}
