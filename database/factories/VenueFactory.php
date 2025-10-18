<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Venue::class;
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
            'v_features' => 'Multimedia Enabled',
            'v_capacity' => fake()->numberBetween(50, 500),
            'v_test_capacity' => fake()->numberBetween(20, 100),
            'v_is_active' => true,
            'department_id' => Department::factory(),
            'manager_id' => User::factory()
        ];
    }
}
