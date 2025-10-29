<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
<<<<<<< HEAD
use App\Models\Venue;
use \Illuminate\Database\Eloquent\Collection;

class DepartmentService {

    public static function updateDepartmentAssignment(Department $department, Venue $venue): void
    {
        $venue->department_id = $department->id;
        $venue->save();
    }

    public static function updateUserDepartment(Department $department, User $manager): void
    {
        $manager->department_id = $department->id;
        $manager->save();
    }

    public static function getDepartmentVenues(Department $department): Collection
    {
        return Department::with('venues')->where('id', $department->id)->get();
=======
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;

class DepartmentService {

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    ///////////////////////////////////////////// CRUD Operations ////////////////////////////////////////////////

    /**
     * Returns the department that has the provided id
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
     * Returns a collection of all the available departments
     *
     * @return Collection
     * @throws Exception
     */
    public function getAllDepartments(): Collection
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
     *          name => 'name_of_department'
     *          code => 'code_of_department'
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
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('Unable to synchronize department data.');}
    }

    /**
     * Deletes the department that contains the specified id
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
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified department.');}
    }

    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////

//    public function updateDepartmentAssignment(Department $department, Venue $venue): void
//    {
//        $venue->department_id = $department->id;
//        $venue->save();
//    }

    /**
     * The method assigns the given department to the given user
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
        }
        catch (ModelNotFoundException $exception) {
            throw $exception;
        }
        catch (Throwable $exception) {
            throw new \Exception('Failed to update the user(s) department.');
        }
    }

    /**
     * This function retrieves all the venues assigned to the provided
     * department
     *
     * @param Department $department
     * @return Collection
     */
    public function getDepartmentVenues(Department $department): Collection
    {
        $department = Department::findOrFail($department->id);

        return $department->venues;
    }

    /**
     * Retrieves the department that contains exactly the name provided
     *
     * @param string $name
     * @return Department|null
     */
    public function findByName(string $name): Department|null
    {
        return Department::where('name', $name)->first();
    }

    /**
     * Retrieves the employees that belong to the same department as the director
     *
     * @param int $director_id
     * @return Collection
     */
    public function getEmployees(int $director_id): Collection
    {
        if ($director_id < 0) {
            throw new InvalidArgumentException('Id must be greater than zero.');
        }

        $director = $this->userService->findUserById($director_id);

        if (!$director || !$director->department_id) {
            throw new InvalidArgumentException('Director is not part of any department.');
        }

        return Department::findOrFail($director->department_id)->employees()->where('id', '<>', $director_id)->get();
>>>>>>> origin/restructuring_and_optimizations
    }
}
