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
        $name = fake()->word();
        return [
            'name' => $name . '.pdf',
            'file_path' => 'documents/' . $name . '.pdf',
            'event_id' => Event::factory(),
        ];
    }

    /**
     * State: no file_path assigned (e.g., before processing or for missing file tests)
     */
    public function withoutFilePath(): static
    {
        return $this->state(function (array $attributes) {
            return [
                // Use empty string to satisfy non-null DB constraints while indicating no stored file
                'file_path' => '',
            ];
        });
    }
}
