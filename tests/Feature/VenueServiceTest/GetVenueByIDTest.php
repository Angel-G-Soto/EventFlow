<?php

use App\Models\Venue;
use App\Models\Department;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\AuditService;

beforeEach(function () {
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService
    );

    $this->department = Department::factory()->create();
});


it('returns the venue when given a valid id', function () {
    $venue = Venue::factory()->create([
        'department_id' => $this->department->id,
    ]);

    $result = $this->venueService->getVenueById($venue->id);

    expect($result)
        ->toBeInstanceOf(Venue::class)
        ->and($result->id)->toBe($venue->id);
});


it('returns null when no venue matches the id', function () {
    $result = $this->venueService->getVenueById(99999);

    expect($result)->toBeNull();
});


it('throws InvalidArgumentException when venue id is negative', function () {
    $this->venueService->getVenueById(-5);
})->throws(InvalidArgumentException::class, 'Venue id must be greater than 0.');
