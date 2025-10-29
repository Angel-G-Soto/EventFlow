<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Models\AuditTrail;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\AuditService;

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

    $this->department = Department::factory()->create();
    $this->venue = Venue::factory()->create(['department_id' => $this->department->id]);

    $this->manager = Mockery::mock(User::class)->makePartial();
    $this->manager->id = 1;
    $this->manager->name = 'Manager';
    $this->manager->department_id = $this->department->id;

    $this->director = Mockery::mock(User::class)->makePartial();
    $this->director->id = 2;
    $this->director->name = 'Director';
    $this->director->department_id = $this->department->id;
});

it('assigns manager successfully', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));
    $this->director->shouldReceive('getRoleNames')->andReturn(collect(['department-director']));

    $this->auditService
        ->shouldReceive('logAction')
        ->once()
        ->andReturn(Mockery::mock(AuditTrail::class));

    $this->venueService->assignManager($this->venue, $this->manager, $this->director);

    expect($this->venue->manager_id)->toBe($this->manager->id);
});

it('throws exception for invalid roles', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['random-role']));
    $this->director->shouldReceive('getRoleNames')->andReturn(collect(['wrong-role']));

    $this->auditService->shouldNotReceive('logAction');

    $this->venueService->assignManager($this->venue, $this->manager, $this->director);
})->throws(InvalidArgumentException::class, 'The manager and the director must be venue-manager or department-director respectively.');

it('throws exception for mismatched departments', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));
    $this->director->shouldReceive('getRoleNames')->andReturn(collect(['department-director']));

    $otherDept = Department::factory()->create();
    $this->manager->department_id = $otherDept->id;

    $this->auditService->shouldNotReceive('logAction');

    $this->venueService->assignManager($this->venue, $this->manager, $this->director);
})->throws(InvalidArgumentException::class, 'The manager and the director must be part of the venue\'s department.');

it('throws generic exception if audit logging fails', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));
    $this->director->shouldReceive('getRoleNames')->andReturn(collect(['department-director']));

    $this->auditService
        ->shouldReceive('logAction')
        ->andThrow(new RuntimeException('Audit failed'));

    $this->venueService->assignManager($this->venue, $this->manager, $this->director);
})->throws(Exception::class, 'Unable to assign the manager to its venue.');
