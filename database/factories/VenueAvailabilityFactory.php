<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\VenueAvailability>
 */
class VenueAvailabilityFactory extends Factory
{
    protected $model = \App\Models\VenueAvailability::class;

    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 10);
        $endHour = $startHour + fake()->numberBetween(6, 10);

        return [
            'venue_id' => Venue::factory(),
            'day' => ucfirst(fake()->dayOfWeek()),
            'opens_at' => sprintf('%02d:00:00', $startHour),
            'closes_at' => sprintf('%02d:00:00', min($endHour, 23)),
        ];
    }
}
