<?php

use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;
use App\Services\AuditService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;

beforeEach(function () {
    $this->auditService = Mockery::mock(AuditService::class);
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);

    $this->venueService = new VenueService(
        departmentService: $this->departmentService,
        useRequirementService: $this->useRequirementService,
        auditService: $this->auditService
    );

    $this->admin = Mockery::mock(User::class)->makePartial();
    $this->admin->id = 99;
    $this->admin->name = 'System Admin';
});


it('soft deletes venues successfully when admin has proper role', function () {
    $venues = Venue::factory()->count(2)->create();

    $this->admin->shouldReceive('getRoleNames')
        ->once()
        ->andReturn(collect(['system-administrator']));

    $this->auditService
        ->shouldReceive('logAdminAction')
        ->twice()
        ->withArgs(function ($adminId, $msg, $desc) use ($venues) {
            expect($adminId)->toBe(99);
            expect($desc)->toContain('Deactivated venue #');
            return true;
        })
        ->andReturn(Mockery::mock(\App\Models\AuditTrail::class));

    $this->venueService->deactivateVenues($venues->all(), $this->admin);

    expect(Venue::withTrashed()->count())->toBe(2)
        ->and(Venue::count())->toBe(0); // all soft-deleted
});


it('throws exception if admin does not have system-administrator role', function () {
    $venues = Venue::factory()->count(1)->create();

    $this->admin->shouldReceive('getRoleNames')
        ->once()
        ->andReturn(collect(['random-role']));

    $this->venueService->deactivateVenues($venues->all(), $this->admin);
})->throws(InvalidArgumentException::class, 'The manager and the director must be system-administrator.');


it('throws exception if the array contains a non-venue element', function () {
    $this->admin->shouldReceive('getRoleNames')
        ->once()
        ->andReturn(collect(['system-administrator']));

    $venue = Venue::factory()->create();
    $invalidList = [$venue, 'not-a-venue'];

    $this->venueService->deactivateVenues($invalidList, $this->admin);
})->throws(InvalidArgumentException::class, 'List contains elements that are not venues.');


it('throws generic exception if delete fails internally', function () {
    $venue = Mockery::mock(Venue::class)->makePartial();
    $venue->id = 1;

    $this->admin->shouldReceive('getRoleNames')
        ->once()
        ->andReturn(collect(['system-administrator']));

    $venue->shouldReceive('delete')
        ->once()
        ->andThrow(new RuntimeException('DB error'));

    $this->auditService->shouldNotReceive('logAdminAction');

    $this->venueService->deactivateVenues([$venue], $this->admin);
})->throws(Exception::class, 'Unable to remove the venues.');
