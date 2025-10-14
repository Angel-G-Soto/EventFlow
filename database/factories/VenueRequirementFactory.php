<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VenueRequirement>
 */
class VenueRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vr_document_link' => $this->faker->url(),
            'vr_document_name' => $this->faker->name(),
            'vr_document_description' => $this->faker->text(),
        ];
    }
}
