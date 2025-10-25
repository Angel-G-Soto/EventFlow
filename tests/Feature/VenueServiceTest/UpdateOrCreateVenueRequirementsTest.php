<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Models\Role;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->service = new VenueService(new DepartmentService());
});

it('throws exception if user is not a department manager', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);
    $user = User::factory()->create(['department_id' => $department->id]);
    $requirementsData = [
        ['name' => 'Req1', 'hyperlink' => 'http://link.com', 'description' => 'Desc']
    ];

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $user);
})->throws(InvalidArgumentException::class, 'Manager does not have the required role.');

it('throws exception if manager does not belong to venue department', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();

    $venue = Venue::factory()->create(['department_id' => $department->id]);
    $manager = User::factory()->create(['department_id' => $otherDepartment->id]);
    $role = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($role);

    $requirementsData = [
        ['name' => 'Req1', 'hyperlink' => 'http://link.com', 'description' => 'Desc']
    ];

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $manager);
})->throws(InvalidArgumentException::class, 'Manager does not belong to the venue department.');

it('throws InvalidArgumentException if a requirement item is not an array', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create(['department_id' => $venue->department_id]);
    $role = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($role);

    $requirementsData = ['invalid requirement'];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Requirement at index 0 must be an array.');

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $manager);
});

it('throws InvalidArgumentException if requirement is missing keys', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create(['department_id' => $venue->department_id]);
    $role = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($role);

    $requirementsData = [
        [
            'name' => 'Requirement 1',
            // missing 'hyperlink'
            'description' => 'Description 1'
        ]
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Missing keys in requirement at index 0: hyperlink');

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $manager);
});

it('throws InvalidArgumentException if a requirement has null values', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create(['department_id' => $venue->department_id]);
    $role = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($role);

    $requirementsData = [
        [
            'name' => null,
            'hyperlink' => 'https://example.com/req1',
            'description' => 'Description 1'
        ]
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("The field 'name' in requirement at index 0 cannot be null");

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $manager);
});

it('successfully creates use requirements', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    $manager = User::factory()->create(['department_id' => $department->id]);
    $role = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($role);

    $requirementsData = [
        ['name' => 'Req1', 'hyperlink' => 'http://link.com', 'description' => 'Desc1'],
        ['name' => 'Req2', 'hyperlink' => 'http://link2.com', 'description' => 'Desc2']
    ];

    $this->service->updateOrCreateVenueRequirements($venue, $requirementsData, $manager);

    $this->assertDatabaseCount('use_requirements', 2);

    foreach ($requirementsData as $doc) {
        $this->assertDatabaseHas('use_requirements', [
            'venue_id' => $venue->id,
            'name' => $doc['name'],
            'hyperlink' => $doc['hyperlink'],
            'description' => $doc['description']
        ]);
    }
});
