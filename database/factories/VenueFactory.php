<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Venue $venue) {
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
                $startHour = fake()->numberBetween(6, 9);
                $endHour = $startHour + fake()->numberBetween(8, 12);

                $venue->availabilities()->create([
                    'day' => $day,
                    'opens_at' => sprintf('%02d:00:00', $startHour),
                    'closes_at' => sprintf('%02d:00:00', min($endHour, 23)),
                ]);
            }
        });
    }

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
            'description' => fake()->sentence(),
            'features' => fake()->randomElement(['1000','0100', '0010', '0001', '1100', '1010', '1001']),
            'capacity' => fake()->numberBetween(50, 500),
            'test_capacity' => fake()->numberBetween(20, 100),
            'department_id' => Department::factory(),
            //'manager_id' => User::factory(),
        ];
    }
}
