<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirement;
use App\Models\Venue;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Throwable;

class VenueService {

    protected DepartmentService $departmentService;
    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService =  $departmentService;
        //$this->auditService = $auditService;
        //$this->EventService = $eventService
    }

    /**
     * Returns a collection of all the available venues within the specified timeframe
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return Collection
     * @throws Exception
     */
    public function getAvailableVenues(DateTime $startTime, DateTime $endTime): Collection
    {
        // Check for error
        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('Start time must be before end time.');
        }
        try {
            // Get events that occur on between the date parameters
            $unavailableEventVenues = Event::where('status', 'approved')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query
                        ->whereBetween('start_time', [$startTime, $endTime])        // Event starts within window
                        ->orWhereBetween('end_time', [$startTime, $endTime])        // Event ends within window
                        ->orWhere(function ($query) use ($startTime, $endTime) {    // Event fully covers window
                            $query->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                })
                ->pluck('venue_id')
                ->unique();

            // Add audit trail

            // Return venues that are not in the approved events.
            return Venue::whereNotIn('id', $unavailableEventVenues)->where('deleted_at', null)->get();
        } catch (\Throwable $exception) {throw new Exception('Unable to extract available venues.');}
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
     * @return Collection
     * @throws Exception
     */
    public function updateOrCreateFromImportData(array $venueData): Collection
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
                $department = Department::where('name', $venue['department'])->first();     // Extract as model method

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
                        //'opening_time' => $venue['opening_time'],
                        //'closing_time' => $venue['closing_time'],
                    ]
                ));
            }

            // Add audit trail

            // Return collection of updated values
            return $updatedVenues;
       }
       catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
       catch (\Throwable $exception) {throw new Exception('Unable to synchronize venue data.');}
    }

    /**
     * This function assigns the manager to the department of the provided menu.
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
            if (! $manager->getRoleNames()->contains('department-manager') || ! $director->getRoleNames()->contains('department-director')) {
                throw new InvalidArgumentException('The manager and the director must be department-manager or department-director respectively.');
            }

            // Validate both are in the correct department
            if ($venue->getDepartmentID() != $manager->department_id || $venue->getDepartmentID() != $director->department_id) {
                throw new InvalidArgumentException('The manager and the director must be part of the venue\'s department.');
            }

            // Assign to the manager, the venue's department
            $this->departmentService->updateUserDepartment($venue->department, $manager);

            // Add audit trail


       }
       catch (InvalidArgumentException $exception) {throw $exception;}
       catch (\Throwable $exception) {throw new Exception('Unable to assign the manager to its venue.');}
    }

    /**
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
            };

            // Add audit trail

        }
        catch (\InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable) {throw new Exception('Unable to remove the venues.');}

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
           // Validate manager role to be 'department-manager' and to belong to the departments of the venues
           if (!$manager->getRoleNames()->contains('department-manager')) {
               throw new \InvalidArgumentException('Manager does not have the required role.');
           }
           elseif (!$manager->department()->where('id', $venue->department_id)->exists()) {
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
           UseRequirement::where('venue_id', $venue->id)->delete();

           // Place requirements
           foreach ($requirementsData as $r) {
               $requirement = new UseRequirement();
               $requirement->venue_id = $venue->id;
               $requirement->name = $r['name'];
               $requirement->hyperlink = $r['hyperlink'];
               $requirement->description = $r['description'];
               $requirement->save();
           }

       }
       catch (InvalidArgumentException $exception) {throw $exception;}
       catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

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
     * @param int $venueId
     * @return Venue|null
     * @throws Exception
     */
    public function getVenueById(int $venueId): ?Venue
    {
        try {
            if ($venueId < 0) {throw new InvalidArgumentException('Venue id must be greater than 0.');}
            return Venue::where('deleted_at', null)->find($venueId);
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable get the venue.');}
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
     * Gets the venues assigned to the provided department
     *
     * @param Department $department
     * @return Collection
     * @throws Exception
     */
    public function getVenuesForDepartment(Department $department): Collection
    {
        try {
            return Venue::where('deleted_at', null)->where('department_id', $department->id)->get();
        }
        catch (\Throwable $exception) {throw new Exception('Unable to get the venues of the department.');}
    }

    /**
     * Returns the use requirements of the specified venue
     *
     * @param int $id
     * @return Collection
     * @throws Exception
     */
    public function getUseRequirements(int $id): Collection
    {
        {
            try {
                if ($id < 0) {throw new InvalidArgumentException('The id must be greater than 0.');}
                $venue = Venue::findOrFail($id);
                //if ($venue == null) {throw new ModelNotFoundException();}
                return $venue->requirements;
            }
            catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
            catch (Throwable $exception) {throw new Exception('We were not able to find the requirements for the venue.');}
        }
    }

}
