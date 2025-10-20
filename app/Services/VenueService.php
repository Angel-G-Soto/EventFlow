<?php
namespace App\Services;

use DateTime;
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
    protected EventService $eventService;

    /**
     * Inject dependencies.
     */
    public function __construct(AuditService $auditService, EventService $eventService)
    {
        $this->auditService = $auditService;
        $this->eventService = $eventService;
    }
    
   /**
     * Retrieves a collection of active venues that are available during a specified time window.
     */
    public function getAvailableVenues(DateTime $startTime, DateTime $endTime): Collection
    {
       if ($startTime >= $endTime) {
                throw new \InvalidArgumentException('Start time must be before end time.');
            }

            $bookedVenueIds = $this->eventService->getBookedVenueIdsAtTime($startTime, $endTime);

            $potentiallyAvailableVenues = Venue::where('v_is_active', true)
                ->whereNotIn('venue_id', $bookedVenueIds)
                ->orderBy('v_name')
                ->get();
                
            return $potentiallyAvailableVenues->filter(fn(Venue $venue) => $venue->isAvailableDuring($startTime, $endTime));
    }

    /**
     * This process is intended to iterate through the submitted array of
     * values related to the Buildings database. It uses the code and name
     * of each room as key identifiers to perform the update. If no record,
     * contains these identifiers, a new record will be created.
     *
     * @param array $venueData Data array of values related to the Buildings database.
     * @return Collection A collection of newly updated or created venues based on import data.
     * @throws Exception
     */
    public function updateOrCreateFromImportData(array $venueData): Venue
    {
        // 1. Determine the department of the incoming Venue
        $department = Department::where('d_name', $venueData['department_name_raw'])->firstOrFail();

        // 2. Map the incoming venueData values to the Venue Schema 
        $attributesToSave = [
            'v_name' => $venueData['v_name'],
            'v_features' => $venueData['v_features'],
            'v_capacity' => $venueData['v_capacity'],
            'v_test_capacity' => $venueData['v_test_capacity'],
            'department_id' => $department->department_id,
            'v_is_active' => true
        ];

        // 3. Return updated/created Venue object
        return Venue::updateOrCreate(['v_code' => $venueData['v_code']], $attributesToSave);
    }

    /**
     * This function assigns the manager to the department of the provided menu.
     * Assigning the manager to a venue of another department, removes privileges
     * from the other and reroutes npending venue requests to the new manager. 
     *
     * @param Venue $venue The venue being assigned a manager
     * @param User $manager The user being assigened a venue
     * @param User $assigner The user (Department Director or Admin) doing the action. 
     * @return Venue Newly updated venue with assigned manager. 
     * @throws Exception
     */
    public function assignManager(Venue $venue, User $newManager, User $assigner): Venue
    {   
        // 1. Update the venue manager and sync
        $oldManagerId = $venue->manager_id;
        $venue->manager_id = $newManager->user_id;
        $venue->save();

        // 2. Automated re-routing of orphaned requests
        if($oldManagerId){
            $this->eventService->reroutePendingVenueApprovals($venue->venue_id, $oldManagerId, $newManager->user_id);
        }

        // 3. Audit the action
        $description = "Assigned user '{$newManager->u_name}' as manager for venue '{$venue->v_name}'.";
        $actionCode = 'VENUE_MANAGER_ASSIGNED';

        if($assigner->is_admin)
            $this->auditService->logAdminAction($assigner->user_id, $assigner->u_name, $actionCode, $description);
        else{
            $this->auditService->logAction($assigner->user_id, $assigner->u_name, $actionCode, $description);
        }

        // 4. Return venue object with assigned manager.
        return $venue;
    }

    /**
     * Soft deletes all the venues provided on the array
     * 
     * @param User $admin The administrator doing the action.
     * @return int Count of deactivated venues.
     */
    public function deactivateAllVenues(User $admin): int
    {
        // 1. Audit the Action
        $description = 'Deactivated all venues as part of the CSV import process.';
        $actionCode = 'ALL_VENUES_DEACTIVATED';

        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);
       
        // 2. Return cunt of deactivated Venues
        return Venue::query()->update(['v_is_active' => false]);
    }

    /**
     * Updates or creates the usage requirements for a specific venue
     * The requirements must be organized as in the following structure:
     *
     * ['documents' => [
     *          ['vr_name' => string, 'vr_content' => string],
     *          ...
     *      ],
     * 'acknowledgements' => [
     *          ['vr_name' => string, 'vr_content' => string],
     *          ...
     *      ]
     * ]
     *
     * @param Venue $venue The venue being updated
     * @param array $requirementData An array containing the requirements data structured as stated above.
     * @param User $editor The user (Venue Manager or Admin) doing the action
     * @return Void
     * @throws Exception
     */
    public function updateOrCreateVenueRequirement(Venue $venue, array $requirementData, User $editor): void
    {
        DB::transaction(function () use ($venue, $requirementData, $editor) {
            // 1. Delete all existing requirements for this venue to ensure a clean sync.
            VenueRequirement::where('venue_id', $venue->venue_id)->delete();

            // 2. Process and create new 'document' type requirements.
            if (!empty($requirementData['documents'])) {
                foreach ($requirementData['documents'] as $document) {
                    VenueRequirement::create([
                        'venue_id'   => $venue->venue_id,
                        'vr_name'    => $document['name'],          // The user-facing name of the document
                        'vr_type'    => 'document',                 // Set the type explicitly
                        'vr_content' => $document['template_url'],  // The content is the URL to the template
                    ]);
                }
            }

            // 3. Process and create new 'acknowledgement' type requirements.
            if (!empty($requirementData['acknowledgements'])) {
                foreach ($requirementData['acknowledgements'] as $acknowledgement) {
                    VenueRequirement::create([
                        'venue_id'   => $venue->venue_id,
                        'vr_name'    => $acknowledgement['label'],          // The user-facing label of the checkbox
                        'vr_type'    => 'acknowledgement',                  // Set the type explicitly
                        'vr_content' => $acknowledgement['description'],    // The content is the descriptive text
                    ]);
                }
            }

            // 4. Log the action.
            $description = "Updated usage requirements for venue '{$venue->v_name}'.";
            $actionCode = 'VENUE_REQUIREMENTS_UPDATED';

            if ($editor->hasRole('system-admin')) {
                $this->auditService->logAdminAction($editor->user_id, $editor->u_name, $actionCode, $description);
            } else {
                $this->auditService->logAction($editor->user_id, $editor->u_name, $actionCode, $description);
            }
        });
    }

    /**
     * Creates a custom query for venues based on the filter parameters.
     * The filters parameter must follow the following structure:
     *
     * [
     *     'v_name' => string,
     *     'v_code' => string,
     *     'v_features' => string,
     *     'v_capacity' => int,
     * ]
     *
     * @param array $filters Desired fileds to filter venues.
     * @return LengthAwarePaginator Paginator containing filtered venues.
     */
    public function getAllVenues(array $filters): LengthAwarePaginator
    {
        $query = Venue::query();

        // 1. Handle text search for 'v_name' using a LIKE query
        if (!empty($filters['v_name'])) {
            $query->where('v_name', 'LIKE', '%' . $filters['v_name'] . '%');
        }

        // 2. Handle exact match for 'v_code'
        if (!empty($filters['v_code'])) {
            $query->where('v_code', $filters['v_code']);
        }

        // 3. Handle minimum capacity search
        if (!empty($filters['v_capacity'])) {
            $query->where('v_capacity', '>=', $filters['v_capacity']);
        }

        // 4. Always return a paginator to match the return type
        return $query->orderBy('v_name')->paginate(15);
    }

     /**
     * Retrieves a single venue by its primary key.
     * 
     * @param int $venueId The primary key (venue_id) of the venue to find.
     * @return User|Error The Eloquent venue object or Excemption if not found.
     */
    public function getVenueById(int $venueId): ?Venue
    {
        return Venue::find($venueId);
    }

    /**
     * Updates the core attributes of the given venue.
     * Attributes must be given on the following array format:
     *
     * [
     *    'v_name' => string,
     *    'v_code' => string,
     *    'v_features' => string,
     *    'v_capacity' => int,
     *    'v_test_capacity' => int,
     *    'v_is_active' => bool
     * ]
     *
     * @param Venue $venue The venue being updated
     * @param array $data  The values to update.
     * @param User $editor The user (Venue Manager or Admin) taking the action
     * @return Venue The newly updated Eloquent Venue object
     * @throws Exception
     */
    public function updateVenue(Venue $venue, array $data, User $editor): Venue
    {
        // 1. Remove the keys that contain null values
        $filteredData = array_filter($data, fn($value)=>!is_null($value));

        // 2. Update the venue with the filtered data
        $venue->update($filteredData);
        
        // 3. Audit the action
        $description = "Updated venue details'{$venue->v_name}'.";
        $actionCode =  'VENUE_UPDATED';

        if($editor->hasRole('system-admin'))
            $this->auditService->logAdminAction($editor->user_id, $editor->u_name, $actionCode, $description);
        else{
            $this->auditService->logAction($editor->user_id, $editor->u_name, $actionCode, $description);
        }
        
        // 4. Return updated Venue object
        return $venue->refresh();
    }

    /**
     * Synchronizes the event type exclusions for a given venue.
     *
     * @param Venue $venue The venue to configure.
     * @param int[] $eventTypeIds An array of IDs for the event types to be excluded.
     * @param User  $editor The manager or admin performing the action.
     * @return void
     */
    public function updateEventTypeExclusions(Venue $venue, array $eventTypeIds, User $editor): void
    {
        // 1. Use the sync() method to update the pivot table.
        $venue->excludedEventTypes()->sync($eventTypeIds);

        // 2. Log the administrative action.
        $description = "Updated event type exclusions for venue '{$venue->v_name}'.";
        $actionCode = 'VENUE_EXCLUSIONS_UPDATED';

        if ($editor->hasRole('system-admin')) {
            $this->auditService->logAdminAction($editor->user_id, $editor->u_name, $actionCode, $description);
        } else {
            $this->auditService->logAction($editor->user_id, $editor->u_name, $actionCode, $description);
        }
    }

    /**
     * Updates or creates the opening hours for a specific venue.
     * The hoursData must be structured as such:
     * ['day_of_week' => value1, 'open_time'=> value2, 'close_time' => value3]
     *
     * @param Venue $venue The venue to be configured.
     * @param array $hoursData An array of opening hours data.
     * @param User $editor The user (Venue Manager or Admin) performing the action.
     * @return void
     */
    public function updateOpeningHours(Venue $venue, array $hoursData, User $editor): void
    {
        // Use a database transaction to ensure data integrity.
        DB::transaction(function () use ($venue, $hoursData) {
            // 1. Delete all existing opening hours for this venue.
            $venue->openingHours()->delete();

            // 2. Loop through the new data and create a record for each day.
            foreach ($hoursData as $dayData) {
                OpeningHour::create([
                    'venue_id' => $venue->venue_id,
                    'day_of_week' => $dayData['day_of_week'],
                    'open_time' => $dayData['open_time'],
                    'close_time' => $dayData['close_time'],
                ]);
            }
        });

        // 3. Audit the action
        $description = "Updated opening hours for venue '{$venue->v_name}'.";
        $actionCode =  'VENUE_HOURS_UPDATED';

        if($editor->hasRole('system-admin'))
            $this->auditService->logAdminAction($editor->user_id, $editor->u_name, $actionCode, $description);
        else{
            $this->auditService->logAction($editor->user_id, $editor->u_name, $actionCode, $description);
        }
    }

    /**
     * Retrieves a collection of all venues managed by a specific user.
     * 
     * @param User $manager The manager being searched
     * @return Collection|Error A collection of venues managed by target user or Excemption if not found. 
     */
    public function getVenuesForManager(User $manager): Collection
    {
        return Venue::where('manager_id', $manager->user_id)->orderBy('v_name')->get();
    }

    /**
     * Gets the venues assigned to the provided department
     *
     * @param Department $department The department being searched
     * @return Collection|Error  A collection of venues managed by target deapartment or Excemption if not found. 
     */
    public function getVenuesForDepartment(Department $department): Collection
    {
        return $department->venues()->orderBy('v_name')->get();
    }

}
