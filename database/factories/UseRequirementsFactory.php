<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UseRequirements>
 */
class UseRequirementsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'us_doc_drive' => fake()->url(),
            'us_instructions' => fake()->paragraph(),
            'us_alcohol_policy' => fake()->boolean(),
            'us_cleanup_policy' => fake()->boolean(),
        ];
    }
}
