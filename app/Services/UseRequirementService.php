<?php

namespace App\Services;
use App\Models\UseRequirement;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

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

            // Add audit trail (best-effort)
            try {
                /** @var AuditService $audit */
                $audit = app(AuditService::class);

                $actor = Auth::user();
                $actorId = $actor?->id ?? null;

                if ($actorId) {
                    $meta = [
                        'requirement_id' => (int) ($requirement->id ?? $id),
                        'venue_id'       => (int) ($requirement->venue_id ?? 0),
                        'name'           => (string) $name,
                        'source'         => 'use_requirement_update',
                    ];
                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $audit->buildContextFromRequest(request(), $meta);
                    }

                    $audit->logAction(
                        (int) $actorId,
                        'requirement',
                        'VENUE_REQUIREMENT_UPDATED',
                        (string) ($requirement->name ?? (string) $requirement->id ?? (string) $id),
                        $ctx
                    );
                }
            } catch (Throwable) {
                // best-effort
            }

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
     * @param array<int,string> $deletedNames Optional list of requirement names being removed (for audit context)
     * @param array<int>|null $deletedIdsForMeta Optional specific IDs to log (used when caller knows the removed IDs)
     * @return bool
     * @throws Exception
     */
    public function deleteVenueUseRequirements(int $venue_id, array $deletedNames = [], ?array $deletedIdsForMeta = null): bool
    {
        try {
            if ($venue_id < 0) throw new InvalidArgumentException('UseRequirement ID must be a positive integer.');

            $query = UseRequirement::where('venue_id', $venue_id);
            if ($query->get()->isEmpty()) { return false;}//throw new ModelNotFoundException("No use requirements found for venue ID {$id}.");}

            $deletedIds = $deletedIdsForMeta ?? $query->pluck('id')->all();
            $deletedCount = $query->delete();

            // Audit: venue requirements deleted (best-effort)
            if ($deletedCount > 0) {
                try {
                    /** @var AuditService $audit */
                    $audit = app(AuditService::class);

                    $actor = Auth::user();
                    $actorId = $actor?->id ?? null;

                    if ($actorId) {
                        $meta = [
                            'venue_id' => (int) $venue_id,
                            'source'   => 'use_requirement_delete',
                        ];
                        if (!empty($deletedIds)) {
                            $meta['deleted_ids'] = $deletedIds;
                        }
                        if (!empty($deletedNames)) {
                            $meta['deleted_names'] = array_values(array_filter($deletedNames));
                        } else {
                            $meta['deleted_count'] = (int) $deletedCount;
                        }
                        $ctx = ['meta' => $meta];
                        if (function_exists('request') && request()) {
                            $ctx = $audit->buildContextFromRequest(request(), $meta);
                        }

                        $targetLabel = !empty($deletedNames)
                            ? (string) $deletedNames[0]
                            : 'Requirements for venue ' . (string) $venue_id;

                        $audit->logAction(
                            (int) $actorId,
                            'requirement',
                            'VENUE_REQUIREMENTS_DELETED',
                            $targetLabel,
                            $ctx
                        );
                    }
                } catch (Throwable) {
                    // best-effort
                }
            }

            return (bool) $deletedCount;
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
