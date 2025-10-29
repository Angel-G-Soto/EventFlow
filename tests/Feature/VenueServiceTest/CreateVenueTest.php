<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Venue;
use App\Services\AuditService;
use App\Services\UseRequirementService;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService,
    );
});

it('creates a venue successfully when data is valid', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'system-admin'])->id);

    $department = Department::factory()->create();

    $data = [
        'department_id' => $department->id,
        'name'          => 'Test Venue',
        'code'          => 'VEN-123',
        'features'      => '0000',
        'capacity'      => 60,
        'test_capacity' => 50,
        'opening_time'  => '08:00',
        'closing_time'  => '22:00',
    ];

    $this->departmentService
        ->shouldReceive('getDepartmentByID')
        ->with($data['department_id'])
        ->andReturn($department);

    $venue = $this->venueService->createVenue($data, $admin);

    expect($venue)->toBeInstanceOf(Venue::class)
        ->and($venue->department_id)->toEqual($data['department_id'])
        ->and($venue->name)->toEqual($data['name'])
        ->and($venue->code)->toEqual($data['code'])
        ->and($venue->features)->toEqual($data['features'])
        ->and($venue->capacity)->toEqual($data['capacity'])
        ->and($venue->test_capacity)->toEqual($data['test_capacity']);
});

it('throws an exception if user is not an admin', function () {
    $user = User::factory()->create();

    $data = [
        'department_id' => 1,
        'name'          => 'Test Venue',
        'code'          => 'VEN-123',
        'features'      => '0000',
        'capacity'      => 60,
        'test_capacity' => 50,
        'opening_time'  => '08:00',
        'closing_time'  => '22:00',
    ];

    $this->venueService->createVenue($data, $user);
})->throws(\InvalidArgumentException::class, 'Only admins can create venues.');

it('throws an exception if required fields are missing', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'system-admin'])->id);

    $data = [
        'department_id' => 1,
        // missing 'name', 'code', etc.
    ];

    $this->venueService->createVenue($data, $admin);
})->throws(\InvalidArgumentException::class, 'Missing required field: name');

it('throws an exception if department does not exist', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'system-admin'])->id);

    $data = [
        'department_id' => 999, // non-existing
        'name'          => 'Test Venue',
        'code'          => 'VEN-123',
        'features'      => '0000',
        'capacity'      => 60,
        'test_capacity' => 50,
        'opening_time'  => '08:00',
        'closing_time'  => '22:00',
    ];

    $this->departmentService
        ->shouldReceive('getDepartmentByID')
        ->with($data['department_id'])
        ->andReturn(null);

    $this->venueService->createVenue($data, $admin);
})->throws(\InvalidArgumentException::class, 'The manager_id or department_id does not exist.');

