<?php

namespace Database\Factories;

use App\Models\OpeningHour;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningHourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OpeningHour::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'day_of_week' => $this->faker->numberBetween(1, 7),
            'open_time' => '08:00:00',
            'close_time' => '17:00:00',
        ];
    }
}
