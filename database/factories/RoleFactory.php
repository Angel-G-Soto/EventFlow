<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Keep role seeds aligned with the allowed roles in the app
        $roles = [
            ['name' => 'user', 'code' => 1],
            ['name' => 'advisor', 'code' => 2],
            ['name' => 'venue-manager', 'code' => 3],
            ['name' => 'event-approver', 'code' => 4],
            ['name' => 'system-admin', 'code' => 5],
            ['name' => 'department-director', 'code' => 6],
        ];

        $role = $this->faker->randomElement($roles);

        return [
            'name' => $role['name'],           // slug-lower identifier consumed by UI validation
            'code' => (string)$role['code'],   // stored as string in schema
        ];
    }
}
