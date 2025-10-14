<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\department_requirement>
 */
class DepartmentRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dr_document_link' => $this->faker->url(),
            'dr_document_name' => $this->faker->name(),
            'dr_document_description' => $this->faker->text(),
        ];
    }
}
