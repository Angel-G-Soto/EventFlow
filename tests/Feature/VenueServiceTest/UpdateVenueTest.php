<?php

use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use App\Models\Role;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->venueService = new VenueService(new DepartmentService());
});

it('updates a venue successfully with valid data', function () {
    $venue = Venue::factory()->create();

    // Create a role and attach to the user (many-to-many)
    $admin = User::factory()->create();
    $department = Department::factory()->create();
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $admin->roles()->attach($role->id);

    $data = [
        'manager_id' => 1,
        'department_id' => $department->id,
        'name' => 'SALON DE CLASES',
        'code' => 'AE-102',
        'features' => '1010',
        'capacity' => 60,
        'test_capacity' => 50,
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
    ];

    $updatedVenue = $this->venueService->updateVenue($venue, $data, $admin);

    expect($updatedVenue)
        ->toBeInstanceOf(Venue::class)
        ->and($updatedVenue->name)->toBe('SALON DE CLASES')
        ->and($updatedVenue->capacity)->toBe(60);
});

it('throws InvalidArgumentException when data contains invalid keys', function () {
    $venue = Venue::factory()->create();

    $admin = User::factory()->create();
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $admin->roles()->attach($role->id);

    $data = [
        'name' => 'Main Hall',
        'invalid_field' => 'Some Value',
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid attribute keys detected');

    $this->venueService->updateVenue($venue, $data, $admin);
});

it('throws InvalidArgumentException when data contains null values', function () {
    $venue = Venue::factory()->create();

    $admin = User::factory()->create();
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $admin->roles()->attach($role->id);

    $data = [
        'name' => null,
        'code' => 'AE-106',
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Null values are not allowed for keys');

    $this->venueService->updateVenue($venue, $data, $admin);
});

it('throws InvalidArgumentException when admin has no system-administrator role', function () {
    $venue = Venue::factory()->create();
    $nonAdmin = User::factory()->create();

    $data = [
        'name' => 'SALON DE CLASES',
        'code' => 'AE-102',
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The manager and the director must be system-administrator.');

    $this->venueService->updateVenue($venue, $data, $nonAdmin);
});
