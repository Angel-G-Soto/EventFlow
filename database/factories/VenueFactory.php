<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\UseRequirements;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    use HasFactory;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'v_name' => fake()->company() . ' Hall',
            'v_code' => fake()->bothify('V??##'),
            //'v_department' => fake()->word(),
            'v_features' => fake()->numberBetween(0000, 9999),
            'v_capacity' => fake()->numberBetween(50, 500),
            'v_test_capacity' => fake()->numberBetween(20, 100),
            'use_requirement_id' => UseRequirements::factory(),
            'department_id' => Department::factory(),
        ];
    }
}
