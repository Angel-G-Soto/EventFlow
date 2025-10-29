<?php

use App\Models\Venue;
use App\Models\UseRequirement;
use App\Services\AuditService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\UserService;
use App\Services\VenueService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

it('returns venue requirements for a valid venue', function () {
    $venue = Venue::factory()->create();

    // Create some requirements for this venue
    $requirements = UseRequirement::factory()->count(3)->create([
        'venue_id' => $venue->id
    ]);

    $result = $this->venueService->getVenueRequirements($venue->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($result->every(fn($requirement) => $requirement->venue_id == $venue->id))->toBeTrue();

});

it('throws an InvalidArgumentException if venue_id is negative', function () {
    $this->venueService->getVenueRequirements(-1);
})->throws(\InvalidArgumentException::class, 'Venue id must be greater than zero.');

it('throws a ModelNotFoundException if venue does not exist', function () {
    $this->venueService->getVenueRequirements(99999); // assuming this ID does not exist
})->throws(ModelNotFoundException::class);
