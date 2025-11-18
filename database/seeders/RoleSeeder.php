<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the canonical roles the application expects.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'user', 'code' => 1],
            ['name' => 'advisor', 'code' => 2],
            ['name' => 'venue-manager', 'code' => 3],
            ['name' => 'event-approver', 'code' => 4],
            ['name' => 'system-admin', 'code' => 5],
            ['name' => 'department-director', 'code' => 6],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'name' => $role['name'],
                    'code' => (string) $role['code'],
                ]
            );
        }
    }
}
