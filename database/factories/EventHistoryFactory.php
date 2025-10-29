<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventHistory>
 */
class EventHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'comment' => fake()->sentence(),
            'event_id' => Event::factory(),
            'approver_id' => User::factory(),
        ];
    }
}
