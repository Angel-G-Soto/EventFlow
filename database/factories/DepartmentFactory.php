<?php

namespace Database\Factories;

<<<<<<< HEAD
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Department::class;

    /**
=======
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
>>>>>>> origin/restructuring_and_optimizations
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
<<<<<<< HEAD
            'd_name' => $this->faker->unique()->company() . ' Department',
            'd_code' => $this->faker->unique()->company()
=======
            'name' => fake()->randomElement(['Ingeniería Mecánica', 'Economía Agrícola', 'Inglés', 'Matemáticas', 'Historia']),
            'code' => 'INITIALS'.fake()->numberBetween(101, 200),
>>>>>>> origin/restructuring_and_optimizations
        ];
    }
}
