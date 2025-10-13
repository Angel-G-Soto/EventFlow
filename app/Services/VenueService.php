<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirements;
use App\Models\Venue;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            if ($venue->department->id == null) {throw new InvalidArgumentException('Venue provided does not belong to a department.');}

            // Assign to the manager, the venue's department
            DepartmentService::updateUserDepartment($venue->department->id, $manager);

            // Add audit trail


        } catch (\Throwable $exception) {throw new Exception('Unable to assign the manager to its venue.');}
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
}
