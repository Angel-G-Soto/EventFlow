<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirement;
use App\Models\Venue;
use App\Models\VenueAvailability;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class VenueService
{
    private const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    protected DepartmentService $departmentService;
    protected UseRequirementService $useRequirementService;
    protected AuditService $auditService;
    protected UserService $userService;

    /**
     * Create a new VenueService instance.
     *
     * @param DepartmentService $departmentService
     * @param UseRequirementService $useRequirementService
     * @param AuditService $auditService
     * @param UserService $userService
     */
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
     *     'description' => string,
     *     'features' => string,
     *     'capacity' => integer,
     *     'test_capacity' => integer,
     *  ]
     *
     * @param array|null $filters
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public function getAllVenues(?array $filters = null): LengthAwarePaginator
    {
        try {

            $query = Venue::query()
                ->with('availabilities')
                ->whereNull('deleted_at');

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
                        default:
                            break;
                    }
                }
            }
            return $query->orderBy('name')->paginate(10);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable to fetch the venues.');
        }
    }

    /**
     * Paginate venues with filtering and lightweight rows for the admin component.
     *
     * @param array<string,mixed> $filters
     * @param int $perPage
     * @param int $page
     * @param array{field?:string|null,direction?:string|null}|null $sort
     */
    public function paginateVenueRows(array $filters = [], int $perPage = 10, int $page = 1, ?array $sort = null): LengthAwarePaginator
    {
        $query = Venue::query()
            ->with(['department'])
            ->whereNull('deleted_at');

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like);
            });
        }

        if (!empty($filters['department_name'])) {
            $departmentName = (string)$filters['department_name'];
            $query->whereHas('department', function ($deptQuery) use ($departmentName) {
                $deptQuery->where('name', $departmentName);
            });
        }

        if (isset($filters['cap_min']) && $filters['cap_min'] !== null && $filters['cap_min'] !== '') {
            $query->where('capacity', '>=', (int)$filters['cap_min']);
        }
        if (isset($filters['cap_max']) && $filters['cap_max'] !== null && $filters['cap_max'] !== '') {
            $query->where('capacity', '<=', (int)$filters['cap_max']);
        }

        $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $field = $sort['field'] ?? null;
        if ($field === 'capacity') {
            $query->orderBy('capacity', $direction);
        } else {
            $query->orderBy('name', $direction);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, $page));
        $paginator->setCollection(
            $paginator->getCollection()->map(function (Venue $venue) {
                return [
                    'id' => (int)$venue->id,
                    'name' => (string)$venue->name,
                    'room' => (string)($venue->code ?? ''),
                    'capacity' => (int)($venue->capacity ?? 0),
                    'department' => (string)(optional($venue->department)->name ?? ''),
                    'opening' => $venue->opening_time ? substr((string)$venue->opening_time, 0, 5) : null,
                    'closing' => $venue->closing_time ? substr((string)$venue->closing_time, 0, 5) : null,
                ];
            })
        );

        return $paginator;
    }

    /**
     * Distinct list of departments that currently own at least one active venue.
     *
     * @return array<int,string>
     */
    public function listVenueDepartments(): array
    {
        return Department::query()
            ->whereNull('deleted_at')
            ->whereHas('venues', function ($builder) {
                $builder->whereNull('deleted_at');
            })
            ->pluck('name')
            ->map(fn($name) => trim((string)$name))
            ->filter(fn($name) => $name !== '')
            ->unique(function ($name) {
                return mb_strtolower($name);
            })
            ->sortBy(fn($name) => mb_strtolower($name))
            ->values()
            ->all();
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
            if ($venue_id < 0) {
                throw new InvalidArgumentException('Venue id must be greater than 0.');
            }
            return Venue::find($venue_id);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable get the venue.');
        }
    }

    /**
     * Retrieve all available venues within a specified timeframe applying optional filters.
     *
     * @param DateTime $start_time
     * @param DateTime $end_time
     * @param array<string,mixed> $filters
     * @return Collection
     * @throws Exception
     */
    public function getAvailableVenues(DateTime $start_time, DateTime $end_time, array $filters = []): Collection
    {
        // Check for error
        if ($start_time >= $end_time) {
            throw new InvalidArgumentException('Start time must be before end time.');
        }
        if ($start_time->format('Y-m-d') !== $end_time->format('Y-m-d')) {
            throw new InvalidArgumentException('Availability lookup must start and end on the same day.');
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
                ->unique()
                ->toArray();

            $day = $start_time->format('l');
            $startHour = $start_time->format('H:i:s');
            $endHour = $end_time->format('H:i:s');

            $query = Venue::whereNotIn('id', $unavailableEventVenues)
                ->with('availabilities')
                ->whereHas('availabilities', function ($query) use ($day, $startHour, $endHour) {
                    $query->where('day', $day)
                        ->where('opens_at', '<=', $startHour)
                        ->where('closes_at', '>=', $endHour);
                });

            $nameFilter = trim((string)($filters['name'] ?? ''));
            if ($nameFilter !== '') {
                $query->where('name', 'like', '%' . $nameFilter . '%');
            }

            $codeFilter = trim((string)($filters['code'] ?? ''));
            if ($codeFilter !== '') {
                $query->where('code', 'like', '%' . $codeFilter . '%');
            }

            $search = trim((string)($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            }

            $capacityFilter = $filters['capacity'] ?? null;
            if ($capacityFilter !== null && $capacityFilter !== '') {
                $query->where('capacity', '>=', (int)$capacityFilter);
            }

            $departmentFilter = $filters['department_id'] ?? null;
            if ($departmentFilter !== null && $departmentFilter !== '') {
                $query->where('department_id', (int)$departmentFilter);
            }

            return $query->get();
        } catch (\Throwable $exception) {
            throw new Exception('Unable to extract available venues.');
        }
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
        if ($venue_id < 0) {
            throw new InvalidArgumentException('Venue id must be greater than zero.');
        }
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
        if ($user_id < 0) {
            throw new InvalidArgumentException('User id must be greater than zero.');
        }
        return Venue::where('department_id', $this->userService->findUserById($user_id)->department->id)->get();
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
        if ($venue_id < 0) {
            throw new InvalidArgumentException('Venue id must be greater than zero.');
        }
        return Venue::findOrFail($venue_id)->requirements;
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

            // Assign to the manager the venue when the schema supports it
            $schema = $venue->getConnection()->getSchemaBuilder();
            if ($schema->hasColumn($venue->getTable(), 'manager_id')) {
                $venue->forceFill(['manager_id' => $manager->id]);
                $venue->save();
            }

            // Audit: director assigns manager to venue
            $directorLabel = $director->name ?? trim(((string)($director->first_name ?? '')) . ' ' . ((string)($director->last_name ?? '')));
            if ($directorLabel === '') {
                $directorLabel = (string)($director->email ?? '');
            }
            $this->auditService->logAction(
                $director->id,
                $directorLabel,
                'ASSIGN_MANAGER',
                'Assigning user ' . $manager->name . '[' . $manager->id . '] to manage ' . $venue->name . ' [' . $venue->id . ']'
            );
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable to assign the manager to its venue.');
        }
    }

    /*

                                                  __  __    _    _   _    _    ____ _____ ____
                                                 |  \/  |  / \  | \ | |  / \  / ___| ____|  _ \
                                                 | |\/| | / _ \ |  \| | / _ \| |  _|  _| | |_) |
                                                 | |  | |/ ___ \| |\  |/ ___ \ |_| | |___|  _ <
                                                 |_|  |_/_/   \_\_| \_/_/   \_\____|_____|_| \_\


     */

    /**
     * Update the availability schedule of a venue.
     *
     * @param Venue $venue
     * @param array<int,array<string,mixed>> $availabilityData
     * @param User $manager
     * @return Venue
     * @throws Exception
     */
    public function updateVenueOperatingHours(Venue $venue, array $availabilityData, User $manager): Venue
    {
        try {

            // Validate manager role to be 'venue-manager' and to belong to the departments of the venues
            if (
                !$manager->getRoleNames()->contains('venue-manager')
                || $manager->department_id !== $venue->department_id
            ) {
                throw new InvalidArgumentException('The user must be venue-manager and belong to the department of the venue.');
            }

            $normalized = $this->normalizeAvailabilityPayload($availabilityData);

            // Audit: operating hours update
            $managerLabel = $manager->name ?? trim(((string)($manager->first_name ?? '')) . ' ' . ((string)($manager->last_name ?? '')));
            if ($managerLabel === '') {
                $managerLabel = (string)($manager->email ?? '');
            }
            $this->auditService->logAction(
                $manager->id,
                $managerLabel,
                'UPDATE_OPERATING_HOURS',
                'Updated availability schedule for venue #' . $venue->id
            );

            $this->syncAvailabilityRecords($venue, $normalized);

            return $venue->refresh()->load('availabilities');
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable to update or create the operating hours.');
        }
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
                throw new InvalidArgumentException('Manager does not have the required role.');
            } elseif (!$manager->department()->where('id', $venue->department_id)->first() != null) {
                throw new InvalidArgumentException('Manager does not belong to the venue department.');
            }

            // Verify that the requirementsData structure is met
            $expectedKeys = ['name', 'hyperlink', 'description'];

            $trimmedData = array_map(function ($requirement) use ($expectedKeys) {
                // Use array_intersect_key to filter only the required keys
                return array_intersect_key($requirement, array_flip($expectedKeys));
            }, $requirementsData);

            foreach ($trimmedData as $i => $doc) {
                if (!is_array($doc)) {
                    throw new InvalidArgumentException("Requirement at index {$i} must be an array.");
                }

                // Must contain all expected keys
                $missingKeys = array_diff($expectedKeys, array_keys($doc));
                if ($missingKeys) {
                    throw new InvalidArgumentException("Missing keys in requirement at index {$i}: " . implode(', ', $missingKeys));
                }

                // No null or empty values
                foreach ($expectedKeys as $key) {
                    if ($doc[$key] == null) {
                        throw new InvalidArgumentException("The field '{$key}' in requirement at index {$i} cannot be null.");
                    }
                }
            }

            // Remove all requirements
            $this->useRequirementService->deleteVenueUseRequirements($venue->id); // MOCK FROM SERVICE

            // Place requirements
            foreach ($trimmedData as $r) {
                $requirement = new UseRequirement();
                $requirement->venue_id = $venue->id;
                $requirement->name = $r['name'];
                $requirement->hyperlink = $r['hyperlink'];
                $requirement->description = $r['description'];
                $requirement->save();
                $managerLabel = $manager->name ?? trim(((string)($manager->first_name ?? '')) . ' ' . ((string)($manager->last_name ?? '')));
                if ($managerLabel === '') {
                    $managerLabel = (string)($manager->email ?? '');
                }
                $this->auditService->logAction(
                    $manager->id,
                    $managerLabel,
                    'CREATE_REQUIREMENT',
                    'Create requirement for venue #' . $venue->id
                );
            }
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable to update or create the venue requirements.');
        }
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
     *      'description' => string|null,
     *      'features' => string,
     *      'capacity' => integer,
     *      'test_capacity' => integer,
     *      'availabilities' => array<array{day:string,opens_at:string,closes_at:string}>
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
            'department_id',
            'name',
            'code',
            'features',
            'capacity',
            'test_capacity'
        ];

        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }

        if ((!$this->departmentService->getDepartmentByID($data['department_id']))) {
            throw new InvalidArgumentException('The manager_id or department_id does not exist.');
        }

        $venue = new Venue();

        $venue->department_id = $data['department_id'];
        $venue->name          = $data['name'];
        $venue->code          = $data['code'];
        $venue->description   = $data['description'] ?? null;
        $venue->features      = $data['features'];
        $venue->capacity      = $data['capacity'];
        $venue->test_capacity = $data['test_capacity'];

        $venue->save();

        $availabilityPayload = [];
        if (!empty($data['availabilities'])) {
            if (!is_array($data['availabilities'])) {
                throw new InvalidArgumentException('Availabilities must be an array of entries.');
            }
            $availabilityPayload = $this->normalizeAvailabilityPayload($data['availabilities']);
        } else {
            $availabilityPayload = $this->buildLegacyAvailabilityPayload($data);
        }

        if (!empty($availabilityPayload)) {
            $this->syncAvailabilityRecords($venue, $availabilityPayload);
        }

        return $venue->load('availabilities');
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
     *     'description' => string|null,
     *     'features' => string,
     *     'capacity' => integer,
     *     'test_capacity' => integer,
     *     'availabilities' => array<array{day:string,opens_at:string,closes_at:string}>
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
        if ($admin && !$admin->getRoleNames()->contains('system-admin')) {
            throw new InvalidArgumentException('The manager and the director must be system-admin.');
        }

        $allowedKeys = array_merge($venue->getFillable(), ['availabilities', 'opening_time', 'closing_time']);

        // Check for invalid keys
        $invalidKeys = array_diff(array_keys($data), $allowedKeys);
        if (!empty($invalidKeys)) {
            throw new InvalidArgumentException(
                'Invalid attribute keys detected: ' . implode(', ', $invalidKeys)
            );
        }

        $availabilityPayload = null;
        $legacyPayload = [];
        if (array_key_exists('availabilities', $data)) {
            if (!is_array($data['availabilities'])) {
                throw new InvalidArgumentException('Availabilities must be an array of entries.');
            }
            $availabilityPayload = $data['availabilities'];
            unset($data['availabilities']);
        } else {
            $legacyPayload = $this->buildLegacyAvailabilityPayload($data);
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

        if ($admin) {
            $this->auditService->logAdminAction(
                $admin->id,
                'venue',
                'VENUE_UPDATED',
                (string)$venue->id,
                ['meta' => ['fields' => array_keys($data)]]
            );
        }

        // Update the venue with the filtered data
        $venue = Venue::updateOrCreate(
            [
                'id' => $venue->id
            ],
            $data
        );

        if ($availabilityPayload !== null) {
            $normalized = $this->normalizeAvailabilityPayload($availabilityPayload);
            $this->syncAvailabilityRecords($venue, $normalized);
        } elseif (!empty($legacyPayload)) {
            $this->syncAvailabilityRecords($venue, $legacyPayload);
        }

        return $venue->load('availabilities');
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
    public function updateOrCreateFromImportData(array $venueData, User $admin, array $context = []): Collection
    {
        try {
            // Iterate through the array
            $updatedVenues = new Collection();

            $allowedImportExtras = ['department', 'department_code', 'department_code_raw', 'department_name_raw', 'availabilities'];
            foreach ($venueData as $venue) {
                // Verify that the requirementsData structure is met

                // Check for invalid keys
                $invalidKeys = array_diff(array_keys($venue), new Venue()->getFillable(), $allowedImportExtras);
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
                $availabilityPayload = null;
                if (array_key_exists('availabilities', $venue)) {
                    if (!is_array($venue['availabilities'])) {
                        throw new InvalidArgumentException('Availabilities must be listed as an array of entries.');
                    }
                    $availabilityPayload = $this->normalizeAvailabilityPayload($venue['availabilities']);
                }
                // Try to resolve department by code first, then by name
                $deptCode = $venue['department_code'] ?? $venue['department_code_raw'] ?? null;
                $deptNameOrCode = $venue['department'] ?? null;
                $department = null;
                if ($deptCode) {
                    $department = $this->departmentService->findByCode($deptCode);
                }
                // If department field is present, try as code first, then as name
                if (!$department && $deptNameOrCode) {
                    $department = $this->departmentService->findByCode($deptNameOrCode);
                }
                if (!$department && $deptNameOrCode) {
                    $department = $this->departmentService->findByName($deptNameOrCode);
                }
                if ($department == null) {
                    throw new ModelNotFoundException('Department [' . ($deptCode ?: $deptNameOrCode) . '] does not exist.');
                }
                $venueModel = Venue::updateOrCreate(
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
                );

                $updatedVenues->add($venueModel);

                if ($availabilityPayload !== null) {
                    $this->syncAvailabilityRecords($venueModel, $availabilityPayload);
                } elseif (
                    $venueModel->wasRecentlyCreated
                    || !$venueModel->availabilities()->exists()
                ) {
                    $this->syncAvailabilityRecords($venueModel, $this->defaultWeekdayAvailability());
                }
            }

            // Audit import action when admin context is available (auth-less supported)
            $ctx = $context;
            if (empty($ctx) && function_exists('request') && request()) {
                $ctx = $this->auditService->buildContextFromRequest(request());
            }
            if ($admin) {
                $this->auditService->logAdminAction(
                    $admin->id,
                    'venue',
                    'VENUES_IMPORTED',
                    'venues_import',
                    $ctx
                );
            }

            // Return collection of updated values
            return $updatedVenues;
        } catch (InvalidArgumentException | ModelNotFoundException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception('Unable to synchronize venue data.');
        }
    }

    /**
     * @param array<int,array<string,mixed>> $availabilityData
     * @return array<int,array{day:string,opens_at:string,closes_at:string}>
     */
    private function normalizeAvailabilityPayload(array $availabilityData): array
    {
        $normalized = [];
        foreach (array_values($availabilityData) as $index => $slot) {
            if (!is_array($slot)) {
                throw new InvalidArgumentException("Availability entry at index {$index} must be an array.");
            }

            $requiredKeys = ['day', 'opens_at', 'closes_at'];
            $missing = array_diff($requiredKeys, array_keys($slot));
            if (!empty($missing)) {
                throw new InvalidArgumentException(
                    'Availability entry at index ' . $index . ' is missing keys: ' . implode(', ', $missing)
                );
            }

            $day = $this->normalizeDay((string)$slot['day'], $index);
            $opensAt = $this->normalizeTimeValue($slot['opens_at'], 'opens_at', $index);
            $closesAt = $this->normalizeTimeValue($slot['closes_at'], 'closes_at', $index);

            if ($opensAt >= $closesAt) {
                throw new InvalidArgumentException("Availability entry at index {$index} must close after it opens.");
            }

            $normalized[] = [
                'day' => $day,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        return $normalized;
    }

    private function normalizeDay(string $day, int $index): string
    {
        $value = ucfirst(strtolower(trim($day)));
        if (!in_array($value, self::DAYS_OF_WEEK, true)) {
            throw new InvalidArgumentException("Availability entry at index {$index} has an invalid day value.");
        }

        return $value;
    }

    /**
     * @param string|Carbon|DateTime $value
     */
    private function normalizeTimeValue($value, string $field, int $index): string
    {
        if ($value instanceof Carbon || $value instanceof DateTime) {
            return $value->format('H:i:s');
        }

        if (is_string($value)) {
            $time = trim($value);
            if ($time === '') {
                throw new InvalidArgumentException("Availability entry at index {$index} has an empty {$field} value.");
            }
            if (strlen($time) === 5) {
                $time .= ':00';
            }

            try {
                $parsed = Carbon::createFromFormat('H:i:s', $time);
            } catch (\Throwable) {
                throw new InvalidArgumentException("Availability entry at index {$index} has an invalid {$field} value.");
            }

            return $parsed->format('H:i:s');
        }

        throw new InvalidArgumentException("Availability entry at index {$index} has an invalid {$field} value.");
    }

    private function syncAvailabilityRecords(Venue $venue, array $slots): void
    {
        $venue->availabilities()->delete();

        foreach ($slots as $slot) {
            $venue->availabilities()->create($slot);
        }
    }

    /**
     * Provide the default Monday–Friday 08:00–17:00 availability slots.
     *
     * @return array<int,array{day:string,opens_at:string,closes_at:string}>
     */
    private function defaultWeekdayAvailability(): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return array_map(function ($day) {
            return [
                'day' => $day,
                'opens_at' => '08:00:00',
                'closes_at' => '17:00:00',
            ];
        }, $days);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function buildLegacyAvailabilityPayload(array &$data): array
    {
        $from = $data['opening_time'] ?? null;
        $to = $data['closing_time'] ?? null;

        unset($data['opening_time'], $data['closing_time']);

        if (!$from || !$to) {
            return [];
        }

        $slots = array_map(function ($day) use ($from, $to) {
            return [
                'day' => $day,
                'opens_at' => $from,
                'closes_at' => $to,
            ];
        }, self::DAYS_OF_WEEK);

        return $this->normalizeAvailabilityPayload($slots);
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
            if ($admin && !$admin->getRoleNames()->contains('system-admin')) {
                throw new InvalidArgumentException('The manager and the director must be system-admin.');
            }

            foreach ($venues as $venue) {
                if (!$venue instanceof Venue) {
                    throw new InvalidArgumentException('List contains elements that are not venues.');
                }
            };

            foreach ($venues as $venue) {
                $venue->delete();
                if ($admin) {
                    $this->auditService->logAdminAction(
                        $admin->id,
                        'venue',
                        'VENUE_DEACTIVATED',
                        (string) $venue->id
                    );
                }
            };
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw new Exception('Unable to remove the venues.');
        }
    }
}
