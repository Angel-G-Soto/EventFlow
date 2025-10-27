<?php

namespace App\Services;
use App\Models\UseRequirement;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;

class UseRequirementService {

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
     * Retrieves all the available categories
     *
     * @return Collection
     * @throws Exception
     */
    public function getAllCategories(): Collection
    {
        try {
            return UseRequirement::all();
        }
        catch (Throwable $exception) {throw new Exception('Unable to retrieve the available categories.');}
    }

    /**
     * Updates the name of the use requirement with the specified id
     *
     * @param int $id
     * @param string $name
     * @return UseRequirement
     * @throws Exception
     */
    public function updateUseRequirement(int $id, string $name): UseRequirement
    {
        try {
            if ($id < 0) {throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');}

            // Find value based on the name. Update its fields
            $requirement = UseRequirement::findOrFail($id);

            $requirement->name = $name;
            $requirement->save();

            // Add audit trail

            // Return collection of updated values
            return $requirement;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to synchronize use requirement data.');}
    }

    /**
     * Deletes the use requirement that contains the specified id
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteUseRequirement(int $id): bool
    {
        try {
            if ($id < 0) throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');

            return UseRequirement::findOrFail($id)->delete();
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified use requirement.');}
    }

    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////
}
