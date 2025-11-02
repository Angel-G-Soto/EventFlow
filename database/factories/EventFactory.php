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
<<<<<<< HEAD
            'current_approver_id' => User::factory(),
            'venue_id' => Venue::factory(),
            'e_organization_nexo_id' => fake()->numberBetween(1, 1000),
            'e_advisor_name' => fake()->name(),
            'e_advisor_email' => fake()->unique()->safeEmail(),
            'e_advisor_phone' => fake()->phoneNumber(),
            'e_organization_name' => fake()->company(),
            'e_title' => fake()->sentence(3),
            'e_type' => fake()->randomElement(['Workshop', 'Seminar', 'Social', 'Conference']),
            'e_description' => fake()->paragraph(),
            'e_status' => fake()->randomElement(['Pending - Advisor', 'Pending - DA', 'Pending - Event Approver', 'Approved', 'Rejected', 'Withdrawn', 'Completed', 'Draft']),
            //'e_status_code' => fake()->numberBetween(100, 999),
            //'e_upload_status' => fake()->randomElement(['Published', 'Draft']),
            'e_start_time' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'e_end_time' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'e_student_id' => fake()->numerify('SID####'),
            'e_student_phone' => fake()->phoneNumber(),
            'e_guests' => fake()->numberBetween(0, 200),
            'e_alcohol_policy_agreement' => fake()->boolean(),
            'e_cleanup_policy_agreement' => fake()->boolean(),
=======
            'venue_id' => Venue::factory(),
            'organization_name' => fake()->unique()->company(),
            'organization_advisor_name' => fake()->unique()->name(),
            'organization_advisor_email' => fake()->unique()->safeEmail(),
            //'organization_advisor_phone' => fake()->unique()->phoneNumber(),
            'creator_institutional_number' => '802' . fake()->numberBetween(1, 25) . str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'creator_phone_number' => fake()->unique()->phoneNumber(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'start_time' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_time' => fake()->dateTimeBetween('+1 hour', '+2 months'),
            'status' => fake()->randomElement([
                'draft',
                'pending - advisor approval',
                'pending - venue manager approval',
                'pending - dsca approval',
                'pending - deanship of administration approval',
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
>>>>>>> origin/restructuring_and_optimizations
        ];
    }

}
