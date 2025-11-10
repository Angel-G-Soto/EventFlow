<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Throwable;

class DepartmentService
{

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    ///////////////////////////////////////////// CRUD Operations ////////////////////////////////////////////////

    /**
     * Retrieve a Department model by its unique identifier.
     *
     * This method fetches the Department record that matches the provided ID.
     * If no department exists with the given ID, the method returns null.
     * The provided ID must be a positive integer; otherwise, an InvalidArgumentException is
     *
     * @param int $id
     * @return Department|null
     */
    public function getDepartmentByID(int $id): Department|null
    {
        if ($id < 0) {
            throw new \InvalidArgumentException('Department ID must be a positive integer.');
        }

        return Department::find($id);
    }

    /**
     * Retrieve a collection of all departments.
     *
     * This method returns all Department records available in the database.
     * If an unexpected error occurs during retrieval, an Exception is thrown.
     *
     * @return Collection
     * @throws Exception
     */
    public function getAllDepartments(): Collection
    {
        try {
            return Department::all();
        } catch (Throwable $exception) {
            throw new Exception('Unable to retrieve departments.');
        }
    }

    /**
     * Create or update multiple departments based on the provided data array.
     *
     * This method iterates through an array of department definitions, validating
     * each entry for the required fields: `name` and `code`. For every department,
     * it will either update the existing record (matched by `name` and `code`) or
     * create a new one if it does not exist. All successfully processed departments
     * are returned as a collection.
     *
     * Expected input structure:
     *
     * [
     *      [
     *          'name' => 'Department of Engineering',
     *          'code' => 'ENG',
     *      ],
     *      [
     *          'name' => 'Department of Science',
     *          'code' => 'SCI',
     *      ],
     *      ...
     * ]
     *
     * @param array $departmentData
     * @return mixed
     * @throws Exception
     */
    public function updateOrCreateDepartment(array $departmentData): Collection
    {
        try {
            // Validate the input data
            foreach ($departmentData as $index => $department) {
                if (!isset($department['name'], $department['code'])) {
                    throw new InvalidArgumentException("Missing required keys 'name' or 'code' in department at index $index.");
                }

                if (!is_string($department['name']) || !is_string($department['code'])) {
                    throw new InvalidArgumentException("Invalid data types in department at index $index. Both 'name' and 'code' must be strings.");
                }
            }

            // Iterate through the array
            $updatedDepartments = new Collection();
            foreach ($departmentData as $department) {

                // Find value based on the name and code. Update its fields
                $updatedDepartments->add(Department::updateOrCreate(
                    [
                        'name' => $department['name'],
                        'code' => $department['code'],
                    ],
                    [
                        'name' => $department['name'],
                        'code' => $department['code'],
                    ]
                ));
            }

            // Add audit trail

            // Return collection of updated values
            return $updatedDepartments;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new Exception('Unable to synchronize department data.');
        }
    }

    /**
     * Delete a department by its unique identifier.
     *
     * This method attempts to locate the Department record with the given ID and perform
     * a soft or hard delete (depending on the model configuration). If the department
     * does not exist or the provided ID is invalid, an appropriate exception is thrown.
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteDepartment(int $id): bool
    {
        try {
            if ($id < 0) throw new InvalidArgumentException('Department ID must be a positive integer.');

            return Department::findOrFail($id)->delete();
        } catch (InvalidArgumentException | ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new Exception('Unable to delete the specified department.');
        }
    }

    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////

    //    public function updateDepartmentAssignment(Department $department, Venue $venue): void
    //    {
    //        $venue->department_id = $department->id;
    //        $venue->save();
    //    }

    /**
     * Assign a user to a specified department.
     *
     * This method updates the given user's associated department by setting their
     * `department_id` to match the provided Department model. Both the Department
     * and User records must exist in the database; otherwise, a ModelNotFoundException
     * will be thrown. Upon successful update, the modified User instance is returned.
     *
     * @param Department $department
     * @param User $manager
     * @return User
     * @throws Exception
     */
    public function updateUserDepartment(Department $department, User $manager): User
    {
        try {
            // Validate that both models exist in the database
            if (!Department::find($department->id) || !$this->userService->findUserById($manager->id)) { // IMPORT AND MOCK USER SERVICE
                throw new ModelNotFoundException('Either the department or the user does not exist in the database.');
            }

            // Update user department
            $manager->department_id = $department->id;
            $manager->save();

            return $manager;
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new \Exception('Failed to update the user(s) department.');
        }
    }


    /**
     * Assign a user to a specified department.
     *
     * This method updates the given user's associated department by setting their
     * `department_id` to match the provided Department model. Both the Department
     * and User records must exist in the database; otherwise, a ModelNotFoundException
     * will be thrown. Upon successful update, the modified User instance is returned.
     *
     * @param Department $department
     * @param User $manager
     * @return User
     * @throws Exception
     */
    public function addUserToDepartment(Department $department, User $manager): User
    {
        try {
            // Validate that both models exist in the database
            if (!Department::find($department->id) || !$this->userService->findUserById($manager->id)) { // IMPORT AND MOCK USER SERVICE
                throw new ModelNotFoundException('Either the department or the user does not exist in the database.');
            }

            // Update user department
            $manager->department_id = $department->id;
            $manager->roles()->attach(Role::where('name', 'venue-manager')->first()->id);
            $manager->save();

            // Add venue manager role

            return $manager;
        }
        catch (ModelNotFoundException $exception) {
            throw $exception;
        }
        catch (Throwable $exception) {
            throw new \Exception('Failed to update the user(s) department.');
        }
    }

    /**
     * Remove a user to a specified department.
     *
     * Both the Department and User records must exist in the database; otherwise, a ModelNotFoundException
     * will be thrown. Upon successful update, the modified User instance is returned.
     *
     * @param Department $department
     * @param User $manager
     * @return User
     * @throws Exception
     */
    public function removeUserFromDepartment(Department $department, User $manager): User
    {
        try {
            // Validate that both models exist in the database
            if (!$this->userService->findUserById($manager->id)) { // IMPORT AND MOCK USER SERVICE
                throw new ModelNotFoundException('Either the department or the user does not exist in the database.');
            }

            // Update user department


            if(!$manager->getRoleNames()->contains('department-director')){
                $manager->department_id = null;
            }

            $manager->roles()->detach(Role::where('name', 'venue-manager')->first()->id);
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

    /**
     * Retrieve all venues associated with a given department.
     *
     * This method fetches the Department model matching the provided instance’s ID
     * and returns the collection of Venue records linked to it through the department’s
     * relationship. If the department does not exist, a ModelNotFoundException is thrown.
     *
     * @param Department $department
     * @return Collection
     */
    public function getDepartmentVenues(Department $department): LengthAwarePaginator
    {
        $department = Department::findOrFail($department->id);

        return $department->venues()->paginate(15);
    }

    /**
     * Retrieve a department by its exact name.
     *
     * This method searches the database for a Department record whose `name`
     * field matches the provided string exactly. If no matching department is found,
     * the method returns null.
     *
     * @param string $name
     * @return Department|null
     */
    public function findByName(string $name): Department|null
    {
        return Department::where('name', $name)->first();
    }

    /**
     * Retrieve a department by its unique code.
     *
     * @param string $code
     * @return Department|null
     */
    public function findByCode(string $code): Department|null
    {
        return Department::where('code', $code)->first();
    }

    /**
     * Retrieve all employees who belong to the same department as the specified director.
     *
     * This method locates the director by their user ID, determines their associated
     * department, and returns a collection of all employees within that department,
     * excluding the director themselves. The provided director ID must be a positive
     * integer and must correspond to a user who is assigned to a department.
     *
     * @param int $director_id
     * @return Collection
     */
    public function getEmployees(int $director_id): LengthAwarePaginator
    {
        if ($director_id < 0) {
            throw new InvalidArgumentException('Id must be greater than zero.');
        }

        $director = $this->userService->findUserById($director_id);

        if (!$director || !$director->department_id) {
            throw new InvalidArgumentException('Director is not part of any department.');
        }

//        return Department::findOrFail($director->department_id)->employees()->where('id', '<>', $director_id)->paginate(15);
        return Department::findOrFail($director->department_id)->employees()->paginate(15);
    }

    public function getVenueManagers(int $director_id): LengthAwarePaginator
    {
        if ($director_id < 0) {
            throw new InvalidArgumentException('Id must be greater than zero.');
        }

        $director = $this->userService->findUserById($director_id);

        if (!$director || !$director->department_id) {
            throw new InvalidArgumentException('Director is not part of any department.');
        }

//        return Department::findOrFail($director->department_id)->employees()->where('id', '<>', $director_id)->paginate(15);
        $dept = Department::findOrFail($director->department_id);

        return $dept->employees()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', 'venue-manager'))
            ->paginate(15); // or ->paginate(15)
    }

    public function getDepartmentManagers(Department $department): LengthAwarePaginator
    {


        return $department->employees()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', 'venue-manager'))
            ->paginate(15); // or ->paginate(15)
    }

}
