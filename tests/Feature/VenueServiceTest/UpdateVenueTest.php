<?php

use App\Models\AuditTrail;
use App\Models\User;
use App\Models\Venue;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\AuditService;
use App\Services\UseRequirementService;
use App\Services\DepartmentService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->auditService = Mockery::mock(AuditService::class);
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService,
    );

    $this->venue = Venue::factory()->create();
    $this->admin = Mockery::mock(User::class)->makePartial();
    $this->admin->id = 99;
    $this->admin->name = 'System Admin';
});


it('updates a venue successfully with valid data and system-administrator role', function () {
    $this->admin->shouldReceive('getRoleNames')->andReturn(collect(['system-administrator']));

    $this->auditService->shouldReceive('logAdminAction')
        ->once()
        ->with($this->admin->id, '', Mockery::type('string'))
        ->andReturn(Mockery::mock(AuditTrail::class));

    $validData = [
        'manager_id' => 1,
        'department_id' => $this->venue->department_id,
        'name' => 'Updated Venue',
        'code' => 'VEN-001',
        'features' => 'New Features',
        'capacity' => 200,
        'test_capacity' => 180,
        'opening_time' => Carbon::now(),
        'closing_time' => Carbon::now()->addHours(8),
    ];

    $updatedVenue = $this->venueService->updateVenue($this->venue, $validData, $this->admin);

    expect($updatedVenue)
        ->toBeInstanceOf(Venue::class)
        ->and($updatedVenue->name)->toBe('Updated Venue');
});


it('throws exception if user does not have system-administrator role', function () {
    $this->admin->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $data = [
        'name' => 'Unauthorized Update',
        'code' => 'VEN-123'
    ];

    $this->venueService->updateVenue($this->venue, $data, $this->admin);
})->throws(InvalidArgumentException::class, 'The manager and the director must be system-administrator.');


it('throws exception if data contains invalid attribute keys', function () {
    $this->admin->shouldReceive('getRoleNames')->andReturn(collect(['system-administrator']));

    $data = [
        'invalid_field' => 'Not allowed',
        'name' => 'Some Venue'
    ];

    $this->venueService->updateVenue($this->venue, $data, $this->admin);
})->throws(InvalidArgumentException::class, 'Invalid attribute keys detected');


it('throws exception if any data field value is null', function () {
    $this->admin->shouldReceive('getRoleNames')->andReturn(collect(['system-administrator']));

    $data = [
        'name' => null,
        'code' => 'VEN-002'
    ];

    $this->venueService->updateVenue($this->venue, $data, $this->admin);
})->throws(InvalidArgumentException::class, 'Null values are not allowed for keys: name');
