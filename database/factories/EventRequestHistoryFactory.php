<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventRequestHistory>
 */
class EventRequestHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'eh_action' => $this->faker->randomElement(['submitted', 'approved', 'rejected', 'commented']),
            'eh_comment' => $this->faker->sentence(),
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
        ];
    }
}
