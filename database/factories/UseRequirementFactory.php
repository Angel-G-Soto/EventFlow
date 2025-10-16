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
            'department_id' => Department::factory(),
            'venue_id' => Venue::factory(),
            'ur_document_link' => fake()->url(),
            'ur_name' => fake()->name(),
            'ur_description' => fake()->realText(),
        ];
    }
}
