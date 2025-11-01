<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->buildingNumber,
            'features' => fake()->randomElement(['1000', '0100', '0010', '0001', '1100', '1010', '1001']),
            'capacity' => fake()->numberBetween(50, 500),
            'test_capacity' => fake()->numberBetween(20, 100),
            'opening_time' => fake()->dateTime(),
            'closing_time' => fake()->dateTime(),
            'department_id' => Department::factory(),
            'manager_id' => User::factory(),
        ];
    }
}
