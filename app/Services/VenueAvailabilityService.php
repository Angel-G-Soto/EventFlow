<?php

namespace App\Services;

use App\Models\VenueAvailability;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class VenueAvailabilityService
{
    /**
     * Retrieve availability rows for a venue.
     *
     * @param int $venueId
     * @return Collection<int,VenueAvailability>
     */
    public function listByVenueId(int $venueId): Collection
    {
        if ($venueId <= 0) {
            throw new InvalidArgumentException('Venue id must be a positive integer.');
        }

        return VenueAvailability::where('venue_id', $venueId)->get();
    }
}
