<?php
namespace App\Services;
use App\Models\Category;
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
        } catch (\Throwable $exception) {throw new Exception('Unable to extract available venues.');}
    }

    /**
     * This process is intended to iterate through the submitted array of
     * values related to the Buildings database. It uses the code and name
     * of each room as key identifiers to perform the update. If no record,
     * contains these identifiers, a new record will be created.
     *
     * @param array $venueData
     * @return Collection
     * @throws Exception
     */
    public static function updateOrCreateFromImportData(array $venueData): Collection
    {
        try {
            // Iterate through the array
            $updatedVenues = new Collection();
            foreach ($venueData as $venue) {

                $department = Department::where('d_name', $venue['v_department'])->first();

                // Model Not Found Error
                If($department->id == null) {
                    throw new ModelNotFoundException('Department ['.$venue['v_department'].'] does not exist.');
                }

                // Find value based on the name and code. Update its fields
                $updatedVenues->add(Venue::updateOrCreate(
                    [
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
                    ]
                ));
            }

            // Add audit trail

            // Return collection of updated values
            return $updatedVenues;
        }
        catch (ModelNotFoundException $exception) {throw $exception;}
        catch (\Throwable $exception) {throw new Exception('Unable to synchronize venue data.');}
    }

    /**
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
     * 'documents' => [
     *          [
     *              'name' => string,
     *              'description' => string,
     *              'template_url' => string
     *          ],
     *          ...
     *      ],
     * 'checkboxes' => [
     *          [
     *              'label' => string
     *          ],
     *          ...
     *      ]
     * ]
     *
     *
     * @param Venue $venue
     * @param array $requirementsData
     * @param User $manager
     * @return Void
     * @throws Exception
     */
    public static function updateOrCreateVenueRequirements(Venue $venue, array $requirementsData, User $manager): Void
    {
       try {

           UseRequirement::where('venue_id', $venue->id)->delete();

           if (!empty($requirementsData['documents'])) {
               foreach ($requirementsData['documents'] as $document) {
                   $requirement = new UseRequirement();
                   $requirement->venue_id = $venue->id;
                   $requirement->ur_document_link = $document['template_url'];
                   $requirement->ur_name = $document['name'];
                   $requirement->ur_description = $document['description'];
                   $requirement->save();
               }
           }

           if (!empty($requirementsData['checkboxes'])) {
               foreach ($requirementsData['checkboxes'] as $checkbox) {
                   $requirement = new UseRequirement();
                   $requirement->venue_id = $venue->id;
                   $requirement->ur_label = $checkbox['label'];
                   $requirement->save();
               }
           }

       } catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

    /**
     * Creates a custom query for venues based on the filter parameters.
     * The filters parameter must follow the following structure:
     *
     * [
     *     'v_name' => value1
     *     'v_code' => value2
     *     'v_features' => value3
     *     'v_capacity' => value4
     *     'v_test_capacity' => value5
     *  ]
     *
     * @param array $filters
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public static function getAllVenues(?array $filters): LengthAwarePaginator
    {
        try {
            $query = Venue::query()->where('deleted_at', null);

            $fillable = new Venue()->getFillable();

            foreach ($filters as $key => $value) {
                if (in_array($key, $fillable) && $value != null) {
                    $query->where($key, $value);
                }
            }

            return $query->paginate(10);
        }catch (\Throwable $exception) {throw new Exception('Unable to fetch the venues.');}
    }

    /**
     * Retrieves the venue that contains the provided ID
     *
     * @param int $venueId
     * @return Venue|null
     * @throws Exception
     */
    public static function getVenueById(int $venueId): ?Venue
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
     *    [
     *      'v_name' => value1
     *     'v_code' => value2
     *     'v_features' => value3
     *     'v_capacity' => value4
     *     'v_test_capacity' => value5
     *     ],
     *      ...
     * ]
     *
     * @param Venue $venue
     * @param array $data
     * @param User $admin
     * @return Venue
     * @throws Exception
     */
    public static function updateVenue(Venue $venue, array $data, User $admin): Venue
    {
        try {

            // Validate admin role

            // Remove the keys that contain null values or are not fillable
            $fillable= new Venue()->getFillable();

            $filteredData = array_filter($data, function($value, $key) use ($fillable) {
                return $value != null && in_array($key, $fillable);
            }, ARRAY_FILTER_USE_BOTH);

            // Update the venue with the filtered data
            Venue::updateOrCreate(
                [
                    'id' => $venue->id
                ],
                $filteredData
            );

            return $venue;
        }
        catch (\Throwable $exception) {throw new Exception('Unable to update or create the venue requirements.');}
    }

    /**
     * Gets the venues assigned to the provided department
     *
     * @param Department $department
     * @return Collection
     * @throws Exception
     */
    public static function getVenuesForDepartment(Department $department): Collection
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
    public static function getUseRequirement(int $id): Collection
    {
        {
            try {
                if ($id == 0 || $id == null) {throw new InvalidArgumentException();}

                return Venue::find($id)->requirements;
            }
            catch (InvalidArgumentException $exception) {throw $exception;}
            catch (Throwable $exception) {throw new Exception('We were not able to find the requirements for the venue.');}
        }
    }

}
