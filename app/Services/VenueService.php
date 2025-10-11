<?php
namespace App\Services;
use App\Models\User;
use App\Models\Event;
use App\Models\UseRequirements;
use App\Models\Venue;
use App\Models\EventRequestHistory;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Psy\Util\Str;
use function Laravel\Prompts\error;

class VenueService {
    public function getAvailableVenues(\DateTime $startTime, \DateTime $endTime): Collection
    {
        // Check for error
        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('Start time must be before end time.');
        }
        // Get events that occur on between the date parameters
        $events = Event::where('e_start_time', '>=', $startTime)
            ->where('e_end_time', '<=', $endTime)
            ->where('e_status', '<>', 'Approved')
            ->get();

        // Get the unique venues being used
        $venueIds = $events->pluck('venue_id')->unique();

        // Get venues that are not in the approved events.
        return  Venue::whereNotIn('id', $venueIds)->get();

    }

    /**
     * This process is intended to iterate through the submitted array of
     * values related to the Buildings database. It uses the code and name
     * of each room as key identifiers to perform the update. If no record,
     * contains these identifiers, a new record will be created.
     *
     * @param array $venueData
     * @return Collection
     */
    public function updateOrCreateFromImportData(array $venueData): Collection
    {
        // Iterate through the array
        $updatedVenues = new Collection();
        foreach ($venueData as $venue) {
            //Find value based on the name and code. Update its fields
           $updatedVenues->add(Venue::updateOrCreate(
                [
                    'v_name' => $venue['v_name'],
                    'v_code' => $venue['v_code'],
                ],
                [
                    'v_name' => $venue['v_name'],
                    'v_code' => $venue['v_code'],
                    'v_department' => $venue['v_department'],
                    'v_features' => $venue['v_features'],
                    'v_capacity' => $venue['v_capacity'],
                    'v_test_capacity' => $venue['v_test_capacity'],
                ]
            ));
        }

        //Update department assignments (IF INCLUDED IN ARRAY)

        //Place audit trail for admin. Auth::user()

        // Return collection of updated values
        return $updatedVenues;
    }

    public function deactivateVenues(array $venues)
    {
        foreach ($venues as $venue) {
            $venue->delete();
            $venue->save();
        };

        //Place audit trail for admin. Auth::user()
    }

    /**
     * Updates or creates the usage requirements for a specific venue
     *
     * @param Venue $venue
     * @param String $hyperlink
     * @param String $instructions
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrCreateVenueRequirements(Venue $venue, String $hyperlink, String $instructions)
    {
        try {
            //Update requirements through the requirements() relation for Venue
            $requirement = Venue::find($venue->id)->requirements;

            $requirement = UseRequirements::updateOrCreate(
                [
                    'id' => $requirement->id,
                ],
                [
                    'us_doc_drive' => $hyperlink,
                    'us_instructions' => $instructions,
                ]);

            $venue->use_requirement_id = $requirement->id;
            $venue->save();

            //Place audit trail for manager. Auth::user()

            return $requirement;
        }
        catch (\Exception $e) {return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }
}
