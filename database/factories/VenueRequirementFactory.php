<?php

namespace Database\Factories;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VenueRequirement>
 */
class VenueRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'vr_name' => $this->faker->name(),
            'vr_content' => $this->faker->text(),
        ];
    }
}
