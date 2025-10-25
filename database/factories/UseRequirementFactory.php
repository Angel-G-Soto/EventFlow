<?php

namespace Database\Factories;

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
            'department_id' => null,
            'venue_id' => null,
            'ur_document_link' => fake()->url(),
            'ur_name' => fake()->words(3, true),
            'ur_description' => fake()->paragraph(),
            'ur_label' => fake()->word(),
        ];
    }
}