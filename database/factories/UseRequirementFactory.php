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
            'ur_document_link' => $this->faker->url(),
            'ur_name' => $this->faker->name(),
            'ur_description' => $this->faker->realText(),
        ];
    }
}
