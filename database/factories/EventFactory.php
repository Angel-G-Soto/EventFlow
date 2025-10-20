<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'current_approver_id' => null,
            'venue_id' => Venue::factory(),
            'event_type_id' => EventType::factory(),

            'e_title' => $this->faker->sentence(4),
            'e_student_id' => $this->faker()->numerify('#########'),
            'e_student_phone' => $this->faker()->phoneNumber,
            'e_description' => $this->faker->paragraph(),
            'e_status' => 'Approved',
            'e_start_time' => Carbon::now()->addDay(),
            'e_end_time' => Carbon::now()->addDay()->addHours(2),
            'sells_food' => $this->faker()->boolean(),
            'uses_institutional_funds' => $this->faker()->boolean(),
            'has_external_guest' => $this->faker()->boolean(),
            'organization_nexo_id' => $this->faker->uuid(),
            'organization_nexo_name' => $this->faker->company() . ' Club',
            'advisor_email' => $this->faker->safeEmail(),
            'e_advisor_phone' => $this->faker()->phoneNumber,
        ];
    }
}
