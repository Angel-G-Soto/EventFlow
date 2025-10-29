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
<<<<<<< HEAD
        return [
            'event_id'     => Event::factory(),
            'd_name'       => $this->faker->words(3, true) . '.txt',
            'd_file_path'  => null,
            'created_at'   => now(),
            'updated_at'   => now(),
=======
        $name = fake()->word();
        return [
            'name' => $name . '.pdf',
            'file_path' => 'documents/' . $name . '.pdf',
            'event_id' => Event::factory(),
>>>>>>> origin/restructuring_and_optimizations
        ];
    }
}
