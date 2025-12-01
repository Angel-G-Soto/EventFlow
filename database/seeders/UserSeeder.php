<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed baseline users that satisfy UI validation rules and role expectations.
     */
    public function run(): void
    {
        // Ensure required roles exist
        $roleNames = ['user', 'advisor', 'venue-manager', 'event-approver', 'system-admin', 'department-director'];
        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name], ['code' => $name]);
        }
        /** @var \Illuminate\Support\Collection<string,Role> $roles */
        $roles = Role::whereIn('name', $roleNames)->get()->keyBy('name');

        // Create a handful of departments so department-required roles stay valid
        $departments = collect([
            ['code' => 'ADM', 'name' => 'Administration'],
            ['code' => 'VEN', 'name' => 'Campus Venues'],
            ['code' => 'ENG', 'name' => 'Engineering'],
        ])->mapWithKeys(function (array $attrs) {
            $department = Department::firstOrCreate(['code' => $attrs['code']], ['name' => $attrs['name']]);
            return [$attrs['code'] => $department];
        });

        $users = [
            [
                'first_name' => 'Alice',
                'last_name' => 'Admin',
                'email' => 'alice.admin@upr.edu',
                'roles' => ['system-admin'],
                'department_code' => 'ADM',
            ],
            [
                'first_name' => 'Dylan',
                'last_name' => 'Director',
                'email' => 'dylan.director@uprm.edu',
                'roles' => ['department-director'],
                'department_code' => 'ENG',
            ],
            [
                'first_name' => 'Valerie',
                'last_name' => 'Venue',
                'email' => 'valerie.venue@upr.edu',
                'roles' => ['venue-manager'],
                'department_code' => 'VEN',
            ],
            [
                'first_name' => 'Erin',
                'last_name' => 'Approver',
                'email' => 'erin.approver@uprm.edu',
                'roles' => ['event-approver'],
                'department_code' => 'ADM',
            ],
            [
                'first_name' => 'Alex',
                'last_name' => 'Advisor',
                'email' => 'alex.advisor@upr.edu',
                'roles' => ['advisor'],
                'department_code' => 'ENG',
            ],
            [
                'first_name' => 'Uma',
                'last_name' => 'User',
                'email' => 'uma.user@uprm.edu',
                'roles' => ['user'],
                'department_code' => 'ADM',
            ],
        ];

        foreach ($users as $data) {
            $department = $departments->get($data['department_code']) ?? $departments->first();

            // Match UsersIndex rule: only roles that require a department
            // (department-director or venue-manager) should be tied to one.
            $rolesForUser = collect($data['roles'])->map(fn ($r) => (string) $r)->all();
            $requiresDept = in_array('department-director', $rolesForUser, true)
                || in_array('venue-manager', $rolesForUser, true);

            $password = bcrypt(str()->random(16));

            $user = User::withTrashed()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'first_name'    => $data['first_name'],
                    'last_name'     => $data['last_name'],
                    'department_id' => $requiresDept ? $department?->id : null,
                    'auth_type'     => 'saml',
                    'password'      => $password,
                    'email_verified_at' => now(),
                ]
            );
            // Ensure soft-deleted baseline users are restored instead of duplicating.
            if ($user->trashed()) {
                $user->restore();
            }

            $attachRoles = collect($data['roles'])
                ->push('user') // guarantee base user role
                ->unique()
                ->map(fn(string $name) => $roles->get($name))
                ->filter();

            if ($attachRoles->isNotEmpty()) {
                $user->roles()->syncWithoutDetaching($attachRoles->pluck('id')->all());
            }
        }
    }
}
