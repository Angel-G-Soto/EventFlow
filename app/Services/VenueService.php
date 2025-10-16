<?php
namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRequirement;
use App\Models\OpeningHour;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VenueService {

    protected AuditService $auditService;
    //protected EventService $eventService

    /**
     * Inject dependencies.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        //this->EventService = $eventService
    }
   /**
     * Retrieves a collection of active venues that are available during a specified time window.
     */
    // public function getAvailableVenues(DateTime $startTime, DateTime $endTime): Collection
    // {
    //    if ($startTime >= $endTime) {
    //             throw new \InvalidArgumentException('Start time must be before end time.');
    //         }

    //         $bookedVenueIds = $this->eventService->getBookedVenueIdsAtTime($startTime, $endTime);

    //         $potentiallyAvailableVenues = Venue::where('is_active', true)
    //             ->whereNotIn('venue_id', $bookedVenueIds)
    //             ->orderBy('v_name')
    //             ->get();
                
    //         return $potentiallyAvailableVenues->filter(fn(Venue $venue) => $venue->isAvailableDuring($startTime, $endTime));
    // }

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
    public function updateOrCreateFromImportData(array $venueData): Venue
    {
        try {
            $department = Department::where('d_name', $venueData['department_name_raw'])->firstOrFail();

            $attributesToSave = [
                'v_name' => $venueData['v_name'],
                'v_features' => $venueData['v_features'],
                'v_capacity' => $venueData['v_capacity'],
                'v_test_capacity' => $venueData['v_test_capacity'],
                'department_id' => $department->department_id,
                'v_is_active' => true // Re-activate the venue as it's in the new file
            ];

            return Venue::updateOrCreate(
                ['v_code' => $venueData['v_code']], // Find by the unique code
                $attributesToSave
            );
        }catch (\Throwable $exception) {throw new \Exception('Unable to update or create from imported data.'. $exception->getMessage(), 0, $exception);}    
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
    public function assignManager(Venue $venue, User $manager, User $assigner): Venue
    {   
        try {
            $venue->manager_id = $manager->user_id;
            $venue->save();

            if($assigner->hasRole('system-admin'))
                $this->auditService->logAdminAction(
                    $assigner->user_id,
                    'VENUE_MANAGER_ASSIGNED',
                    "Assigned user '{$manager->u_name}' as manager for venue '{$venue->v_name}'."
                );
            else{
                $this->auditService->logAction(
                    $assigner->user_id,
                    'VENUE_MANAGER_ASSIGNED',
                    "Assigned user '{$manager->u_name}' as manager for venue '{$venue->v_name}'."
                );
            }
            return $venue;
        }catch (\Throwable $exception) {throw new \Exception('Unable assign manager to venue.'. $exception->getMessage(), 0, $exception);}
    }

    /**
     * Soft deletes all the venues provided on the array
     */
    public function deactivateAllVenues(User $admin): int
    {
        $this->auditService->logAdminAction(
            $admin->user_id,
            'ALL_VENUES_DEACTIVATED',
            'Deactivated all venues as part of the CSV import process.'
        );
      return Venue::query()->update(['v_is_active' => false]);
    }

    /**
     * Updates or creates the usage requirements for a specific venue
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
     * @param Venue $venue
     * @param array $requirementData
     * @param User $manager
     * @return Void
     * @throws Exception
     */
    public function updateOrCreateVenueRequirement(Venue $venue, array $requirementData, User $editor): Void
    {
        DB::transaction(function () use ($venue, $requirementData, $editor) {

            // Delete all exisitng requirements for this venue
            VenueRequirement::where('venue_id', $venue->venue_id)->delete();

           if (!empty($requirementData['documents'])) {
               foreach ($requirementData['documents'] as $document) {
                   $requirement = new VenueRequirement();
                   $requirement->venue_id = $venue->venue_id;
                   $requirement->vr_drive_link = $document['drive_url'];
                   $requirement->vr_label = $document['label'];
                   $requirement->vr_description = $document['description'];
                   $requirement->save();
               }
           }

           if (!empty($requirementData['acknowledgements'])) {
               foreach ($requirementData['acknowledgements'] as $acknowledgement) {
                   $requirement = new VenueRequirement();
                   $requirement->venue_id = $venue->id;
                   $requirement->vr_label = $acknowledgement['label'];
                   $requirement->vr_description = $acknowledgement['description'];
                   $requirement->save();
               }
           }
           if($editor->hasRole('system-admin'))
                $this->auditService->logAdminAction(
                    $editor->user_id,
                    'VENUE_REQUIREMENTS_UPDATED',
                    "Updated usage requirements for venue '{$venue->v_name}'."
                );
            else{
                $this->auditService->logAction(
                    $editor->user_id,
                   'VENUE_REQUIREMENTS_UPDATED',
                "Updated usage requirements for venue '{$venue->v_name}'."
                );
            }
        });
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
     * @param bool $paginate
     * @return Collection|LengthAwarePaginator
     */
    public function getAllVenues(array $filters): LengthAwarePaginator
    {
        $query = Venue::query();

        // Handle text search for 'v_name' using a LIKE query
        if (!empty($filters['v_name'])) {
            $query->where('v_name', 'LIKE', '%' . $filters['v_name'] . '%');
        }

        // Handle exact match for 'v_code'
        if (!empty($filters['v_code'])) {
            $query->where('v_code', $filters['v_code']);
        }

        // Handle minimum capacity search
        if (!empty($filters['v_capacity'])) {
            $query->where('v_capacity', '>=', $filters['v_capacity']);
        }

        // Always return a paginator to match the return type
        return $query->orderBy('v_name')->paginate(15);
    }

     /**
     * Retrieves a single venue by its primary key.
     */
    public function getVenueById(int $venueId): ?Venue
    {
        return Venue::find($venueId);
    }

    /**
     * Updates the attributes of the given menu.
     * Attributes must be given on the following array format:
     *
     * [
     *    'v_name' => value1
     *    'v_code' => value2
     *    'v_features' => value3
     *    'v_capacity' => value4
     *    'v_test_capacity' => value5
     * ]
     *
     * @param Venue $venue
     * @param array $data
     * @param User $admin
     * @return Venue
     * @throws Exception
     */
    public function updateVenue(Venue $venue, array $data, User $editor): Venue
    {
        try {
            // Remove the keys that contain null values
            $filteredData = array_filter($data, fn($value)=>!is_null($value));

            // Update the venue with the filtered data
            $venue->update($filteredData);
            
           if($editor->hasRole('system-admin'))
                $this->auditService->logAdminAction(
                    $editor->user_id,
                    'VENUE_REQUIREMENTS_UPDATED',
                    "Updated usage requirements for venue '{$venue->v_name}'."
                );
            else{
                $this->auditService->logAction(
                    $editor->user_id,
                   'VENUE_REQUIREMENTS_UPDATED',
                "Updated usage requirements for venue '{$venue->v_name}'."
                );
            }
            
            return $venue->refresh();
        } catch (\Throwable $exception) {throw new \Exception('Unable to update or create the venue requirements.'. $exception->getMessage(), 0, $exception);}
    }

    /**
     * Updates or creates the opening hours for a specific venue.
     *
     * @param Venue $venue The venue to be configured.
     * @param array $hoursData An array of opening hours data.
     * @param User $admin The administrator performing the action.
     * @return void
     */
    public function updateOpeningHours(Venue $venue, array $hoursData, User $editor): void
    {
        // Use a database transaction to ensure data integrity.
        // If any part of this process fails, all changes will be rolled back.
        DB::transaction(function () use ($venue, $hoursData) {
            // First, delete all existing opening hours for this venue.
            $venue->openingHours()->delete();

            // Loop through the new data and create a record for each day.
            foreach ($hoursData as $dayData) {
                // The controller should validate that the required keys exist.
                OpeningHour::create([
                    'venue_id' => $venue->venue_id,
                    'day_of_week' => $dayData['day_of_week'],
                    'open_time' => $dayData['open_time'],
                    'close_time' => $dayData['close_time'],
                ]);
            }
        });

       if($editor->hasRole('system-admin'))
                $this->auditService->logAdminAction(
                    $editor->user_id,
                    'VENUE_REQUIREMENTS_UPDATED',
                    "Updated usage requirements for venue '{$venue->v_name}'."
                );
        else{
            $this->auditService->logAction(
                $editor->user_id,
                'VENUE_REQUIREMENTS_UPDATED',
                "Updated usage requirements for venue '{$venue->v_name}'."
            );
        }
    }

     /**
     * Retrieves a collection of all venues managed by a specific user.
     */
    public function getVenuesForManager(User $manager): Collection
    {
        return Venue::where('manager_id', $manager->user_id)->orderBy('v_name')->get();
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
        return $department->venues()->orderBy('v_name')->get();
    }

}
