<?php
namespace App\Services;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirement;
use App\Models\Venue;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * Retrieve a Venue by its ID.
     *
     * This method attempts to find a venue with the given ID.
     * If the provided ID is less than 0, an InvalidArgumentException is thrown.
     * If any other error occurs during the retrieval, a generic Exception is thrown.
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
     * Retrieve all available venues within a specified timeframe.
     *
     * This method returns a collection of venues that are not booked for any approved events
     * overlapping with the provided start and end times. The timeframe is inclusive: any event
     * starting, ending, or fully covering the window will mark the venue as unavailable.
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
        } catch (\Throwable $exception) {throw new Exception('Unable to extract available venues.');}
    }

    /**
     * Find a Venue by its ID.
     *
     * This method attempts to retrieve a Venue model that matches the given ID.
     * The ID must be a positive integer. If no venue is found, the method returns null.
     * @param int $venue_id
     * @return Venue|null
     */
    public function findByID(int $venue_id): ?Venue
    {
        if ($venue_id < 0) {throw new InvalidArgumentException('Venue id must be greater than zero.');}
        return Venue::find($venue_id);
    }

    /**
     * Retrieve all venues associated with the department of a specific user.
     *
     * This method fetches all Venue records where the department matches the department
     * of the user with the provided ID. The user ID must be a positive integer.
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
     * Assign a manager to a venue.
     *
     * This method assigns a given manager to the specified venue. If the venue
     * already has a manager from another department, their privileges are removed.
     * Both the manager and director must have the appropriate roles and belong
     * to the same department as the venue.
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
     * Update the operating hours of a venue.
     *
     * This method updates the opening and closing times of the given venue.
     * The operation is performed by a manager with the 'venue-manager' role.
     * If the venue does not exist, it will be created with the provided ID and hours.
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
     * Update or create usage requirements for a specific venue.
     *
     * This method replaces all existing usage requirements of the given venue
     * with the provided set. Each requirement must follow the structure below:
     *
     * [
     *      [
     *         'name' => string,        // The name of the requirement
     *         'hyperlink' => string,   // Optional URL for the requirement
     *         'description' => string  // Description of the requirement
     *      ],
     *      ...
     * ]
     *
     * The operation can only be performed by a manager with the 'venue-manager' role
     * who belongs to the same department as the venue.
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
     * Retrieve all usage requirements for a specific venue.
     *
     * This method fetches the requirements associated with the venue identified
     * by the given ID. The venue ID must be a positive integer. If no venue is
     * found with the provided ID, the method will throw a `ModelNotFoundException`.
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
     * Create a new venue.
     *
     * This method allows a system administrator to create a new venue with the
     * provided data. All required fields must be present, and the specified
     * department must exist. Only users with the 'system-admin' role are allowed
     * to perform this operation.
     *
     * [
     *      'manager_id' => integer,
     *      'department_id' => integer,
     *      'name' => string,
     *      'code' => string,
     *      'features' => string,
     *      'capacity' => integer,
     *      'test_capacity' => integer,
     *      'opening_time' => time,
     *      'closing_time' => time,
     *   ]
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
        //try {

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
        //}
        //catch (InvalidArgumentException $exception) {throw $exception;}
        //catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

    /**
     * This process is intended to iterate through the submitted array of
     * values related to the Buildings database. It uses the code and name
     * of each room as key identifiers to perform the update. If no record,
     * contains these identifiers, a new record will be created.
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
    {
        try {
            // Iterate through the array
            $updatedVenues = new Collection();

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
                }

                // Find value based on the name and code. Update its fields
                $updatedVenues->add(Venue::updateOrCreate(
                    [
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
                    ]
                ));
            }

            $this->auditService->logAdminAction($admin->id, '', 'Updated venues from import data.'); // MOCK FROM SERVICE

            // Return collection of updated values
            return $updatedVenues;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to synchronize venue data.');}
    }

    /**
     * Soft deletes (deactivates) multiple venues.
     *
     * This method allows a system administrator to deactivate one or more venues.
     * Each element in the `$venues` array must be an instance of the `Venue` model.
     * Soft deletion ensures that the venue data is retained in the database but
     * marked as deleted. An audit log is created for each deactivated venue.
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
        }
        catch (\InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable) {throw new Exception('Unable to remove the venues.');}

    }
}
