<?php

namespace App\Services;
use App\Models\UseRequirement;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;

class UseRequirementService {

    public function __construct(){}
    ///////////////////////////////////////////// CRUD Operations ////////////////////////////////////////////////

    /**
     * Return the use requirement with the corresponding id
     *
     * @param int $id
     * @return UseRequirement
     * @throws Exception
     */
    public function getUseRequirementByID(int $id): UseRequirement
    {
        try {
            if ($id < 0) {throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');}

            return UseRequirement::findOrFail($id);
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to find a use requirement with that ID.');}
    }

    /**
     * Updates the name of the use requirement with the specified id
     *
     * @param int $id
     * @param string $name
     * @param string $hyperlink
     * @param string $description
     * @return UseRequirement
     * @throws Exception
     */
    public function updateUseRequirement(int $id, string $name, string $hyperlink, string $description): UseRequirement
    {
        try {
            if ($id < 0) {throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');}

            // Find value based on the name. Update its fields
            $requirement = UseRequirement::findOrFail($id);

            $requirement->name = $name;
            $requirement->hyperlink = $hyperlink;
            $requirement->description = $description;
            $requirement->save();

            // Add audit trail

            // Return collection of updated values
            return $requirement;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to synchronize use requirement data.');}
    }


    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////

    /**
     * Deletes the use requirements that belong to the specified venue.
     *
     * @param int $venue_id
     * @return bool
     * @throws Exception
     */
    public function deleteVenueUseRequirements(int $venue_id): bool
    {
        try {
            if ($venue_id < 0) throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');

            if (UseRequirement::where('venue_id', $venue_id)->get()->isEmpty()) { return false;}//throw new ModelNotFoundException("No use requirements found for venue ID {$id}.");}

            return UseRequirement::where('venue_id', $venue_id)->delete();
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified use requirement.');}
    }

//    /**
//     * Retrieves the use requirements that belong to the specified venue.
//     *
//     * @param int $venue_id
//     * @return Collection
//     * @throws Exception
//     */
//    public function getVenueUseRequirements(int $venue_id): Collection
//    {
//        try {
//            if ($venue_id < 0) throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');
//
//            return UseRequirement::where('venue_id', $venue_id)->get();
//        }
//        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified use requirement.');}
//    }
}
