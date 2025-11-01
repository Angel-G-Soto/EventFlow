<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use LightSaml\Model\Metadata\Organization;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'venue_id' => Venue::factory(),
            'organization_nexo_id' => fake()->numberBetween(1, 500),
            'organization_nexo_name' => fake()->unique()->company(),
            'organization_advisor_email' => fake()->unique()->email(),
            'organization_advisor_name' => fake()->unique()->name(),
            'organization_advisor_phone' => fake()->unique()->phoneNumber(),
            'student_number' => '802' . fake()->numberBetween(1, 25) . fake()->unique()->numberBetween(0001, 9999),
            'student_phone' => fake()->unique()->phoneNumber(),
            // Use an event-like title instead of a person honorific
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
            'start_time' => fake()->dateTime(),
            'end_time' => fake()->dateTime(),
            'status' => fake()->randomElement(['draft', 'pending approval - advisor', 'pending approval - manager', 'pending approval - event approver', 'pending approval - deanship of administration', 'approved', 'rejected', 'cancelled', 'withdrawn', 'completed']),
            'guests' => fake()->numberBetween(10, 100),
            'handles_food' => fake()->boolean(),
            'use_institutional_funds' => fake()->boolean(),
            'external_guest' => fake()->boolean(),
        ];
    }
}
