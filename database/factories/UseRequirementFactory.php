<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UseRequirement>
 */
class UseRequirementFactory extends Factory
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
            'hyperlink' => fake()->url(),
            'name' => fake()->name(),
            'description' => fake()->realText(),
        ];
    }
}
