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
            'organization_name' => fake()->unique()->company(),
            'organization_advisor_name' => fake()->unique()->name(),
            'organization_advisor_email' => fake()->unique()->safeEmail(),
            'organization_advisor_phone' => fake()->unique()->phoneNumber(),
            'creator_institutional_number' => '802' . fake()->numberBetween(1, 25) . str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'creator_phone_number' => fake()->unique()->phoneNumber(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'start_time' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_time' => fake()->dateTimeBetween('+1 hour', '+2 months'),
            'status' => fake()->randomElement([
                // 'draft',
                'pending - advisor approval',
                'pending - venue manager approval',
                'pending - dsca approval',
                // 'pending - deanship of administration approval',
                'approved',
                'rejected',
                'cancelled',
                'withdrawn',
                'completed'
            ]),
            'guest_size' => fake()->numberBetween(10, 100),
            'handles_food' => fake()->boolean(),
            'use_institutional_funds' => fake()->boolean(),
            'external_guest' => fake()->boolean(),
        ];
    }

}
