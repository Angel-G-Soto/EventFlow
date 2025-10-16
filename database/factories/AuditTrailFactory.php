<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditTrailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuditTrail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Associate the audit trail record with a new, fake user.
            'user_id' => User::factory(),

            // Provide a sample action code.
            'at_action' => $this->faker->randomElement(['EVENT_CREATED', 'USER_UPDATED', 'VENUE_DELETED']),

            // Provide a descriptive sentence.
            'at_description' => $this->faker->sentence(),

            // Randomly set whether it was an admin action or not.
            'is_admin_action' => $this->faker->boolean(),
        ];
    }
}
