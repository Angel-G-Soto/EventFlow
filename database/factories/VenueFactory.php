<?php

namespace Database\Factories;

use App\Models\Department;
<<<<<<< HEAD
=======
use App\Models\User;
>>>>>>> origin/restructuring_and_optimizations
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
<<<<<<< HEAD
    use HasFactory;
=======
>>>>>>> origin/restructuring_and_optimizations
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
<<<<<<< HEAD
            'v_name' => fake()->company() . ' Hall',
            'v_code' => fake()->bothify('V??##'),
            'v_features' => fake()->numberBetween(0000, 9999),
            'v_capacity' => fake()->numberBetween(50, 500),
            'v_test_capacity' => fake()->numberBetween(20, 100),
            'department_id' => Department::factory(),
=======
            'name' => fake()->company(),
            'code' => fake()->unique()->buildingNumber,
            'features' => fake()->randomElement(['1000','0100', '0010', '0001', '1100', '1010', '1001']),
            'capacity' => fake()->numberBetween(50, 500),
            'test_capacity' => fake()->numberBetween(20, 100),
            'opening_time' => fake()->dateTime(),
            'closing_time' => fake()->dateTime(),
            'department_id' => Department::factory(),
            'manager_id' => User::factory(),
>>>>>>> origin/restructuring_and_optimizations
        ];
    }
}
