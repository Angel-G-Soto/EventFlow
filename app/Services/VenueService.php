<?php
namespace App\Services;
<<<<<<< HEAD
use App\Models\Department;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirements;
use App\Models\Venue;
=======
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirement;
use App\Models\Venue;
use Carbon\Carbon;
>>>>>>> origin/restructuring_and_optimizations
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
<<<<<<< HEAD
use App\Services\DepartmentService;
use InvalidArgumentException;

class VenueService {
    /**
     * Returns a collection of all the available venues within the specified timeframe
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return Collection
     * @throws Exception
     */
    public static function getAvailableVenues(DateTime $startTime, DateTime $endTime): Collection
    {
        // Check for error
        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('Start time must be before end time.');
        }
        try {
            // Get events that occur on between the date parameters
            $events = Event::where('e_start_time', '>=', $startTime)
                ->where('e_end_time', '<=', $endTime)
                ->where('e_status', '<>', 'Approved')
                ->where('e_status', '<>', 'Completed')
                ->get();

            // Get the unique venues being used
            $venueIds = $events->pluck('venue_id')->unique();

            // Add audit trail

            // Return venues that are not in the approved events.
            return Venue::whereNotIn('id', $venueIds)->where('deleted_at', null)->get();
=======
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class VenueService {

    protected DepartmentService $departmentService;
    protected UseRequirementService $useRequirementService;
    protected AuditService $auditService;
    protected UserService $userService;

    public function __construct(DepartmentService $departmentService, UseRequirementService $useRequirementService, AuditService $auditService, UserService $userService)
    {
        $this->departmentService =  $departmentService;
        $this->useRequirementService = $useRequirementService;
        $this->auditService = $auditService;
        $this->userService = $userService;
        //$this->EventService = $eventService
    }

/*
                              ____ _____ _   _ _____ ____      _    _       _   _ ____  _____
                             / ___| ____| \ | | ____|  _ \    / \  | |     | | | / ___|| ____|
                            | |  _|  _| |  \| |  _| | |_) |  / _ \ | |     | | | \___ \|  _|
                            | |_| | |___| |\  | |___|  _ <  / ___ \| |___  | |_| |___) | |___
                             \____|_____|_| \_|_____|_| \_\/_/   \_\_____|  \___/|____/|_____|
*/

    /**
     * Creates a custom query for venues based on the filter parameters.
     * The filters parameter must follow the following structure:
     *
     * [
     *     'manager_id' => integer,
     *     'department_id' => integer,
     *     'name' => string,
     *     'code' => string,
     *     'features' => string,
     *     'capacity' => integer,
     *     'test_capacity' => integer,
     *     'opening_time' => time,
     *     'closing_time' => time,
     *  ]
     *
     * @param array|null $filters
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public function getAllVenues(?array $filters = null): LengthAwarePaginator
    {
        try {

            $query = Venue::query()->whereNull('deleted_at');

            if (!empty($filters)) {
                // Verify that the requirementsData structure is met

                // Check for invalid keys
                $invalidKeys = array_diff(array_keys($filters), new Venue()->getFillable());
                if (!empty($invalidKeys)) {
                    throw new InvalidArgumentException(
                        'Invalid attribute keys detected: ' . implode(', ', $invalidKeys)
                    );
                }

                // Check for null values
                $nullKeys = array_keys(array_filter($filters, function ($value) {
                    return is_null($value);
                }));
                if (!empty($nullKeys)) {
                    throw new InvalidArgumentException(
                        'Null values are not allowed for keys: ' . implode(', ', $nullKeys)
                    );
                }

                foreach ($filters as $key => $value) {
                    if ($value === null) {
                        continue;
                    }
                    switch ($key) {
                        case 'manager_id':
                        case 'department_id':
                            $query->where($key, (int) $value);
                            break;
                        case 'name':
                        case 'code':
                            $query->where($key, 'like', '%' . $value . '%');
                            break;
                        case 'features':
                            $query->where($key, $value);
                            break;
                        case 'capacity':
                        case 'test_capacity':
                            $query->where($key, '>=', (int) $value);
                            break;
                        case 'opening_time':
                        case 'closing_time':
                            $query->whereTime($key, '=', $value);
                            break;
                        default:
                            break;
                    }
                }
            }
            return $query->orderBy('name')->paginate(10);
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to fetch the venues.');}
    }

    /**
     * Retrieves the venue that contains the provided ID
     *
     * @param int $venue_id
     * @return Venue|null
     * @throws Exception
     */
    public function getVenueById(int $venue_id): ?Venue
    {
        try {
            if ($venue_id < 0) {throw new InvalidArgumentException('Venue id must be greater than 0.');}
            return Venue::find($venue_id);
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable get the venue.');}
    }


    /**
     * Get the venues that are provided in the id array
     *
     * @param array $ids
     * @return mixed
     */
    public static function getVenuesByIds(array $ids)
    {
        foreach ($ids as $id) {
            if ($id <= 0) {
                throw new InvalidArgumentException("Venue IDs must be positive integers.");
            }
        }

        $venues = Venue::whereIn('id', $ids)->get();

        if ($venues->count() !== count($ids)) {
            throw new ModelNotFoundException("One or more venues not found.");
        }

        return $venues;
    }

    /**
     * Returns a collection of all the available venues within the specified timeframe
     *
     * @param DateTime $start_time
     * @param DateTime $end_time
     * @return Collection
     * @throws Exception
     */
    public function getAvailableVenues(DateTime $start_time, DateTime $end_time): Collection
    {
        // Check for error
        if ($start_time >= $end_time) {
            throw new \InvalidArgumentException('Start time must be before end time.');
        }
        try {
            // Get events that occur on between the date parameters (// MOCK FROM EVENT SERVICE)
            $unavailableEventVenues = Event::where('status', 'approved')
                ->where(function ($query) use ($start_time, $end_time) {
                    $query
                        ->whereBetween('start_time', [$start_time, $end_time])        // Event starts within window
                        ->orWhereBetween('end_time', [$start_time, $end_time])        // Event ends within window
                        ->orWhere(function ($query) use ($start_time, $end_time) {    // Event fully covers window
                            $query->where('start_time', '<=', $start_time)
                                ->where('end_time', '>=', $end_time);
                        });
                })
                ->pluck('venue_id')
                ->unique();

            // Return venues that are not in the approved events.
            return Venue::whereNotIn('id', $unavailableEventVenues)->get();
>>>>>>> origin/restructuring_and_optimizations
        } catch (\Throwable $exception) {throw new Exception('Unable to extract available venues.');}
    }

    /**
<<<<<<< HEAD
=======
     * Finds the venue model that contains the provided id
     *
     * @param int $venue_id
     * @return Venue|null
     */
    public function findByID(int $venue_id): ?Venue
    {
        if ($venue_id < 0) {throw new InvalidArgumentException('Venue id must be greater than zero.');}
        return Venue::find($venue_id);
    }

    /**
     *
     *
     * @param int $user_id
     * @return Collection
     */
    public function getVenuesWithDirectorId(int $user_id): Collection
    {
        if ($user_id < 0) {throw new InvalidArgumentException('Venue id must be greater than zero.');}
        return Venue::where('department_id', $this->userService->findUserById($user_id)->department->id)->get();
    }

/*

                                         ____ ___ ____  _____ ____ _____ ___  ____
                                        |  _ \_ _|  _ \| ____/ ___|_   _/ _ \|  _ \
                                        | | | | || |_) |  _|| |     | || | | | |_) |
                                        | |_| | ||  _ <| |__| |___  | || |_| |  _ <
                                        |____/___|_| \_\_____\____| |_| \___/|_| \_\

 */

    /**
     * This function assigns the manager to the venue.
     * Assigning the manager to a venue of another department, removes privileges
     * from the other.
     *
     * @param Venue $venue
     * @param User $manager
     * @param User $director
     * @return void
     * @throws Exception
     */
    public function assignManager(Venue $venue, User $manager, User $director): void
    {
        try {
            // Validate director and manager have the appropriate roles
            if (!$manager->getRoleNames()->contains('venue-manager') || !$director->getRoleNames()->contains('department-director')) {
                throw new InvalidArgumentException('The manager and the director must be venue-manager or department-director respectively.');
            }

            // Validate both are in the same department
            if ($venue->getDepartmentID() != $manager->department_id || $venue->getDepartmentID() != $director->department_id) {
                throw new InvalidArgumentException('The manager and the director must be part of the venue\'s department.');
            }

            // CALL EVENT SERVICE METHOD // MOCK IT // reroutePendingVenueApprovals($venue->venue_id, $oldManagerId, $newManager->user_id);

            // Assign to the manager the venue
            $venue->manager()->associate($manager);

            $this->auditService->logAction($director->id,
                '',
                'Assigning user ' . $manager->name . '[' . $manager->id . '] to manage ' . $venue->name . ' [' . $venue->id . ']'); // MOCK FROM SERVICE

        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to assign the manager to its venue.');}
    }

/*

                                              __  __    _    _   _    _    ____ _____ ____
                                             |  \/  |  / \  | \ | |  / \  / ___| ____|  _ \
                                             | |\/| | / _ \ |  \| | / _ \| |  _|  _| | |_) |
                                             | |  | |/ ___ \| |\  |/ ___ \ |_| | |___|  _ <
                                             |_|  |_/_/   \_\_| \_/_/   \_\____|_____|_| \_\


 */

    /**
     * Updates the attributes of the given menu.
     * Attributes must be given on the following array format:
     *
     * [
     *     'opening_time' => time,
     *     'closing_time' => time,
     *  ]
     *
     * @param Venue $venue
     * @param Carbon $opening_hours
     * @param Carbon $closing_hours
     * @param User $manager
     * @return Venue
     * @throws Exception
     */
    public function updateVenueOperatingHours(Venue $venue, Carbon $opening_hours, Carbon $closing_hours, User $manager): Venue
    {
        try {

            // Validate admin role
            if (!$manager->getRoleNames()->contains('venue-manager')) {
                throw new InvalidArgumentException('The user must be venue-manager.');
            }

            $this->auditService->logAction($manager->id,'', 'Updated operating hours for venue #'.$venue->id); // MOCK FROM SERVICE

            // Update the venue with the filtered data
            return Venue::updateOrCreate(
                [
                    'id' => $venue->id
                ],
                [
                    'opening_time' => $opening_hours,
                    'closing_time' => $closing_hours,
                ]
            );
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to update or create the operating hours.');}
    }

    /**
     * Updates or creates the usage requirements for a specific venue.
     *
     * The requirements must be organized as in the following structure:
     *
     * [
     *      [
     *          'name' => string,
     *          'hyperlink' => string,
     *          'description' => string
     *      ],
     *      ...
     *  ]
     *
     *
     * @param Venue $venue
     * @param array $requirementsData
     * @param User $manager
     * @return Void
     * @throws Exception
     */
    public function updateOrCreateVenueRequirements(Venue $venue, array $requirementsData, User $manager): Void
    {
        try {
            // Validate manager role to be 'venue-manager' and to belong to the departments of the venues
            if (!$manager->getRoleNames()->contains('venue-manager')) {
                throw new \InvalidArgumentException('Manager does not have the required role.');
            }
            elseif (!$manager->department()->where('id', $venue->department_id)->first() != null) {
                throw new \InvalidArgumentException('Manager does not belong to the venue department.');
            }

            // Verify that the requirementsData structure is met
            $expectedKeys = ['name', 'hyperlink', 'description'];

            foreach ($requirementsData as $i => $doc) {
                if (!is_array($doc)) {
                    throw new \InvalidArgumentException("Requirement at index {$i} must be an array.");
                }

                // Must contain all expected keys
                $missingKeys = array_diff($expectedKeys, array_keys($doc));
                if ($missingKeys) {
                    throw new \InvalidArgumentException("Missing keys in requirement at index {$i}: " . implode(', ', $missingKeys));
                }

                // No null or empty values
                foreach ($expectedKeys as $key) {
                    if ($doc[$key] == null) {
                        throw new \InvalidArgumentException("The field '{$key}' in requirement at index {$i} cannot be null.");
                    }
                }
            }

            // Remove all requirements
            $this->useRequirementService->deleteVenueUseRequirements($venue->id); // MOCK FROM SERVICE

            // Place requirements
            foreach ($requirementsData as $r) {
                $requirement = new UseRequirement();
                $requirement->venue_id = $venue->id;
                $requirement->name = $r['name'];
                $requirement->hyperlink = $r['hyperlink'];
                $requirement->description = $r['description'];
                $requirement->save();
                $this->auditService->logAction($manager->id, '', 'Create requirement for venue #'.$venue->id); // MOCK FROM SERVICE
            }


        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

    /**
     * Retrieves the requirements of the venue with the id provided
     *
     * @param int $venue_id
     * @return Collection
     */
    public function getVenueRequirements(int $venue_id): Collection
    {
        if ($venue_id < 0) {throw new InvalidArgumentException('Venue id must be greater than zero.');}
        return Venue::findOrFail($venue_id)->requirements;
    }

/*

                             _    ____  __  __ ___ _   _ ___ ____ _____ ____      _  _____ ___  ____
                            / \  |  _ \|  \/  |_ _| \ | |_ _/ ___|_   _|  _ \    / \|_   _/ _ \|  _ \
                           / _ \ | | | | |\/| || ||  \| || |\___ \ | | | |_) |  / _ \ | || | | | |_) |
                          / ___ \| |_| | |  | || || |\  || | ___) || | |  _ <  / ___ \| || |_| |  _ <
                         /_/   \_\____/|_|  |_|___|_| \_|___|____/ |_| |_| \_\/_/   \_\_| \___/|_| \_\


 */

    /**
     * Create a new Venue.
     *
     * @param array $data
     * @param User $admin
     * @return Venue
     * @throws InvalidArgumentException
     */
    public function createVenue(array $data, User $admin): Venue
    {
        if (!$admin->getRoleNames()->contains('system-admin')) {
            throw new InvalidArgumentException('Only admins can create venues.');
        }

        // Validate mandatory fields exist
        $requiredKeys = [
            'department_id', 'name', 'code',
            'features', 'capacity', 'test_capacity'
        ];

        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }

        if((!$this->departmentService->getDepartmentByID($data['department_id'])))
        {
            throw new InvalidArgumentException('The manager_id or department_id does not exist.');
        }

        $venue = new Venue();

        $venue->department_id = $data['department_id'];
        $venue->name          = $data['name'];
        $venue->code          = $data['code'];
        $venue->features      = $data['features'];
        $venue->capacity      = $data['capacity'];
        $venue->test_capacity = $data['test_capacity'];
        $venue->opening_time  = $data['opening_time'];
        $venue->closing_time  = $data['closing_time'];

        $venue->save();

        return $venue;
    }

    /**
     * Updates the attributes of the given menu.
     * Attributes must be given on the following array format:
     *
     * [
     *     'manager_id' => integer,
     *     'department_id' => integer,
     *     'name' => string,
     *     'code' => string,
     *     'features' => string,
     *     'capacity' => integer,
     *     'test_capacity' => integer,
     *     'opening_time' => time,
     *     'closing_time' => time,
     *  ]
     *
     * @param Venue $venue
     * @param array $data
     * @param User $admin
     * @return Venue
     * @throws Exception
     */
    public function updateVenue(Venue $venue, array $data, User $admin): Venue
    {
        try {

            // Validate admin role
            if (!$admin->getRoleNames()->contains('system-administrator')) {
                throw new InvalidArgumentException('The manager and the director must be system-administrator.');
            }

            // Check for invalid keys
            $invalidKeys = array_diff(array_keys($data), $venue->getFillable());
            if (!empty($invalidKeys)) {
                throw new InvalidArgumentException(
                    'Invalid attribute keys detected: ' . implode(', ', $invalidKeys)
                );
            }

            // Check for null values
            $nullKeys = array_keys(array_filter($data, function ($value) {
                return is_null($value);
            }));
            if (!empty($nullKeys)) {
                throw new InvalidArgumentException(
                    'Null values are not allowed for keys: ' . implode(', ', $nullKeys)
                );
            }

            $this->auditService->logAdminAction($admin->id,'', 'Updated venue #'.$venue->id); // MOCK FROM SERVICE

            // Update the venue with the filtered data
            return Venue::updateOrCreate(
                [
                    'id' => $venue->id
                ],
                $data
            );
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

    /**
>>>>>>> origin/restructuring_and_optimizations
     * This process is intended to iterate through the submitted array of
     * values related to the Buildings database. It uses the code and name
     * of each room as key identifiers to perform the update. If no record,
     * contains these identifiers, a new record will be created.
<<<<<<< HEAD
     *
     * @param array $venueData
     * @return Collection
     * @throws Exception
     */
    public static function updateOrCreateFromImportData(array $venueData): Collection
=======
     * The `$venueData` array must contain one or more associative arrays,
     * each representing a venue with the following keys:
     *
     * Example:
     * [
     *   [
     *     'name'          => 'SALON DE CLASES',    // string - Venue name
     *     'code'          => 'AE-102',             // string - Unique venue code
     *     'department'    => 'ADEM',               // string - Department code
     *     'features'      => '0101',               // string - Comma-separated features, [online, multimedia, teaching, computers]
     *     'capacity'      => 50,                   // int - Maximum room capacity
     *     'test_capacity' => 40,                   // int - Capacity during exams
     *   ],
     *   ...
     * ]
     *
     * @param array $venueData
     * @param User $admin
     * @return Collection
     * @throws Exception
     */
    public function updateOrCreateFromImportData(array $venueData, User $admin): Collection
>>>>>>> origin/restructuring_and_optimizations
    {
        try {
            // Iterate through the array
            $updatedVenues = new Collection();
<<<<<<< HEAD
            foreach ($venueData as $venue) {

                $department = Department::where('d_name', $venue['v_department'])->first();

                // Model Not Found Error
                If($department->id == null) {
                    throw new ModelNotFoundException('Department ['.$venue['v_department'].'] does not exist.');
=======

            foreach ($venueData as $venue)
            {
                // Verify that the requirementsData structure is met

                // Check for invalid keys
                $invalidKeys = array_diff(array_keys($venue), new Venue()->getFillable(), ['department']);
                if (!empty($invalidKeys)) {
                    throw new InvalidArgumentException(
                        'Invalid attribute keys detected: ' . implode(', ', $invalidKeys)
                    );
                }

                // Check for null values
                $nullKeys = array_keys(array_filter($venue, function ($value) {
                    return is_null($value);
                }));
                if (!empty($nullKeys)) {
                    throw new InvalidArgumentException(
                        'Null values are not allowed for keys: ' . implode(', ', $nullKeys)
                    );
                }
            }

            foreach ($venueData as $venue) {

                // Find the department with its name. EX. Mechanical Engineering
                $department = $this->departmentService->findByName($venue['department']);     // MOCK IT FROM DEPARTMENT SERVICE

                // Model Not Found Error
                If($department == null) {
                    throw new ModelNotFoundException('Department ['.$venue['department'].'] does not exist.');
>>>>>>> origin/restructuring_and_optimizations
                }

                // Find value based on the name and code. Update its fields
                $updatedVenues->add(Venue::updateOrCreate(
                    [
<<<<<<< HEAD
                        'v_name' => $venue['v_name'],
                        'v_code' => $venue['v_code'],
                    ],
                    [
                        'v_name' => $venue['v_name'],
                        'v_code' => $venue['v_code'],
                        'department_id' => $department->id,
                        'v_features' => $venue['v_features'],
                        'v_capacity' => $venue['v_capacity'],
                        'v_test_capacity' => $venue['v_test_capacity'],
=======
                        'name' => $venue['name'],
                        'code' => $venue['code'],
                    ],
                    [
                        'name' => $venue['name'],
                        'code' => $venue['code'],
                        'department_id' => $department->id,
                        'features' => $venue['features'],
                        'capacity' => $venue['capacity'],
                        'test_capacity' => $venue['test_capacity'],
>>>>>>> origin/restructuring_and_optimizations
                    ]
                ));
            }

<<<<<<< HEAD
            // Add audit trail
=======
            $this->auditService->logAdminAction($admin->id, '', 'Updated venues from import data.'); // MOCK FROM SERVICE
>>>>>>> origin/restructuring_and_optimizations

            // Return collection of updated values
            return $updatedVenues;
        }
<<<<<<< HEAD
        catch (ModelNotFoundException $exception) {throw $exception;}
=======
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
>>>>>>> origin/restructuring_and_optimizations
        catch (\Throwable $exception) {throw new Exception('Unable to synchronize venue data.');}
    }

    /**
<<<<<<< HEAD
     * This function assigns the manager to the department of the provided menu.
     * Assigning the manager to a venue of another department, removes privileges
     * from the other.
     *
     * @param Venue $venue
     * @param User $manager
     * @param User $admin
     * @return void
     * @throws Exception
     */
    public static function assignManager(Venue $venue, User $manager, User $admin): void
    {
        try {
            // Validate admin and manager have the appropriate roles


            // Validate the venue has a department
            if ($venue->department_id == null) {throw new InvalidArgumentException('Venue provided does not belong to a department.');}

            // Assign to the manager, the venue's department
            DepartmentService::updateUserDepartment($venue->department, $manager);

            // Add audit trail


        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to assign the manager to its venue.');}
    }

    /**
     * Soft deletes all the venues provided on the array
     *
     * @param array $venues
     * @return void
     * @throws Exception
     */
    public static function deactivateVenues(array $venues): void
    {
        try {
            foreach ($venues as $venue) {
                if (!$venue instanceof Venue) {throw new \InvalidArgumentException('List contains elements that are not venues.');}
                $venue->delete();
            };

            // Add audit trail

=======
     * Soft deletes all the venues provided on the array
     *
     * @param array $venues
     * @param User $admin
     * @return void
     * @throws Exception
     */
    public function deactivateVenues(array $venues, User $admin): void
    {
        try {
            // Validate admin role
            if (!$admin->getRoleNames()->contains('system-administrator')) {
                throw new InvalidArgumentException('The manager and the director must be system-administrator.');
            }

            foreach ($venues as $venue) {
                if (!$venue instanceof Venue) {throw new \InvalidArgumentException('List contains elements that are not venues.');}
            };

            foreach ($venues as $venue) {
                $venue->delete();
                $this->auditService->logAdminAction($admin->id, '', 'Deactivated venue #'.$venue->id);  // MOCK FROM SERVICE
            };
>>>>>>> origin/restructuring_and_optimizations
        }
        catch (\InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable) {throw new Exception('Unable to remove the venues.');}

    }
<<<<<<< HEAD

    /**
     * Updates or creates the usage requirements for a specific venue
     *
     * @param Venue $venue
     * @param String $hyperlink
     * @param String $instructions
     * @param bool $alcohol_policy
     * @param bool $cleanup_policy
     * @param User $manager
     * @return Collection
     * @throws Exception
     */
    public static function updateOrCreateVenueRequirements(Venue $venue, String $hyperlink, String $instructions, Bool $alcohol_policy, Bool $cleanup_policy, User $manager): Venue
    {

       try {
            //Update requirements through the requirements() relation for Venue
            $requirement = Venue::find($venue->id)->requirements;

            $requirement = UseRequirements::updateOrCreate(
                [
                    'id' => $requirement ? $requirement->id : null,
                ],
                [
                    'us_doc_drive' => $hyperlink,
                    'us_instructions' => $instructions,
                    'us_alcohol_policy' => $alcohol_policy,
                    'us_cleanup_policy' => $cleanup_policy,
                ]);

            $venue->use_requirement_id = $requirement->id;
            $venue->save();

            //Place audit trail for manager. Auth::user()

            return $venue;
       } catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }
=======
>>>>>>> origin/restructuring_and_optimizations
}
