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
     * @return Department|null
     */
    public static function getDepartmentByID(int $id): Department|null
    {
        if ($id < 0) {
            throw new \InvalidArgumentException('Department ID must be a positive integer.');
        }

        return Department::find($id);
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
        catch (Throwable $exception) {throw new Exception('Unable to retrieve departments.');}
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
            // Validate the input data
            foreach ($departmentData as $index => $department) {
                if (!isset($department['d_name'], $department['d_code'])) {
                    throw new InvalidArgumentException("Missing required keys 'd_name' or 'd_code' in department at index $index.");
                }

                if (!is_string($department['d_name']) || !is_string($department['d_code'])) {
                    throw new InvalidArgumentException("Invalid data types in department at index $index. Both 'd_name' and 'd_code' must be strings.");
                }
            }

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
            if ($id < 0) throw new InvalidArgumentException('Department ID must be a positive integer.');

            return Department::findOrFail($id)->delete();
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (ModelNotFoundException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('Unable to delete the specified department.');}
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
                if ($id < 0) {throw new InvalidArgumentException('Department ID must be a positive integer.');}
                $department = Department::findOrFail($id);
                return $department->requirements;
            }
            catch (InvalidArgumentException $exception) {throw $exception;}
            catch (ModelNotFoundException $exception) {throw $exception;}
            catch (Throwable $exception) {throw new Exception('Unable to retrieve department requirements.');}
        }
    }


//    public static function updateDepartmentAssignment(Department $department, Venue $venue): void
//    {
//        $venue->department_id = $department->id;
//        $venue->save();
//    }
//

    /**
     * The method assigns the given department to the given user
     *
     * @param Department $department
     * @param User $manager
     * @return void
     * @throws Exception
     */
    public static function updateUserDepartment(Department $department, User $manager): User
    {
        try {
            // Validate that both models exist in the database
            if (!Department::find($department->id) || !User::find($manager->id)) {
                throw new ModelNotFoundException('Either the department or the user does not exist in the database.');
            }

            // Update user department
            $manager->department_id = $department->id;
            $manager->save();

            return $manager;
        }
        catch (ModelNotFoundException $exception) {
            throw $exception;
        }
        catch (Throwable $exception) {
            throw new \Exception('Failed to update the user(s) department.');
        }
    }
//
//    public static function getDepartmentVenues(Department $department): Collection
//    {
//        return Department::with('venues')->where('id', $department->id)->get();
//    }
}
