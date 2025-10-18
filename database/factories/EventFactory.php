<?php

namespace Database\Factories;

use App\Models\Event;
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
            'e_title' => $this->faker->sentence(4),
            'e_description' => $this->faker->paragraph(),
            'e_status' => 'Approved',
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'organization_nexo_id' => $this->faker->uuid(),
            'organization_name' => $this->faker->company() . ' Club',
            'advisor_email' => $this->faker->safeEmail(),
            'creator_id' => User::factory(),
            'current_approver_id' => null,
            'venue_id' => Venue::factory(),
        ];
    }
}
