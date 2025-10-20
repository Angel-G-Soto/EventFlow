<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'd_name' => fake()->word() . '.pdf',
            'd_file_path' => 'documents/' . fake()->uuid . '.pdf',
            'event_id' => Event::factory(),
        ];
    }
}
