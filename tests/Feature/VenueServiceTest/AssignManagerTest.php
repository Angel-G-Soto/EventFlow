<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Models\Role;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $departmentService = new DepartmentService();
    $this->service = new VenueService($departmentService);
});


it('assigns a manager successfully when roles and departments match', function () {
    $department = Department::factory()->create();

    $venue = Venue::factory()->create(['department_id' => $department->id]);

    $manager = User::factory()->create(['department_id' => $department->id]);
    $managerRole = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($managerRole);

    $director = User::factory()->create(['department_id' => $department->id]);
    $directorRole = Role::factory()->create(['name' => 'department-director']);
    $director->roles()->attach($directorRole);

    $this->service->assignManager($venue, $manager, $director);

    expect(true)->toBeTrue(); // passes if no exception
});

it('throws exception if manager does not have department-manager role', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    $manager = User::factory()->create(['department_id' => $department->id]);
    $managerRole = Role::factory()->create(['name' => 'lecturer']);
    $manager->roles()->attach($managerRole);

    $director = User::factory()->create(['department_id' => $department->id]);
    $directorRole = Role::factory()->create(['name' => 'department-director']);
    $director->roles()->attach($directorRole);

    $this->service->assignManager($venue, $manager, $director);
})->throws(InvalidArgumentException::class, 'The manager and the director must be department-manager or department-director respectively.');

it('throws exception if director does not have department-director role', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    $manager = User::factory()->create(['department_id' => $department->id]);
    $managerRole = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($managerRole);

    $director = User::factory()->create(['department_id' => $department->id]);
    $directorRole = Role::factory()->create(['name' => 'lecturer']);
    $director->roles()->attach($directorRole);

    $this->service->assignManager($venue, $manager, $director);
})->throws(InvalidArgumentException::class, 'The manager and the director must be department-manager or department-director respectively.');

it('throws exception if users belong to different departments', function () {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();

    $venue = Venue::factory()->create(['department_id' => $departmentA->id]);

    $manager = User::factory()->create(['department_id' => $departmentB->id]);
    $managerRole = Role::factory()->create(['name' => 'department-manager']);
    $manager->roles()->attach($managerRole);

    $director = User::factory()->create(['department_id' => $departmentA->id]);
    $directorRole = Role::factory()->create(['name' => 'department-director']);
    $director->roles()->attach($directorRole);

    $this->service->assignManager($venue, $manager, $director);
})->throws(InvalidArgumentException::class, 'The manager and the director must be part of the venue\'s department.');
