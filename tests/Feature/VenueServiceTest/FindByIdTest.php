<?php

use App\Models\Venue;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\AuditService;
use App\Services\UserService;

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

it('returns the venue when a valid id is provided', function () {
    $venue = Venue::factory()->create();

    $foundVenue = $this->venueService->findByID($venue->id);

    expect($foundVenue)->toBeInstanceOf(Venue::class)
        ->id->toEqual($venue->id);
});

it('returns null when the venue does not exist', function () {
    $nonExistingId = 9999;
    $foundVenue = $this->venueService->findByID($nonExistingId);

    expect($foundVenue)->toBeNull();
});

it('throws an InvalidArgumentException if id is negative', function () {
    $negativeId = -1;

    $this->venueService->findByID($negativeId);
})->throws(\InvalidArgumentException::class, 'Venue id must be greater than zero.');
