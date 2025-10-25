<?php

use App\Models\Venue;
use App\Services\VenueService;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->venueService = new VenueService(new DepartmentService());
});

it('returns use requirements for a valid venue id', function () {
    // Create a venue and associate some requirements with it.
    $venue = Venue::factory()->create();
    $requirement1 = $venue->requirements()->create([
        'name' => 'Requirement 1',
        'hyperlink' => 'https://example.com/req1',
        'description' => 'Description for requirement 1',
    ]);
    $requirement2 = $venue->requirements()->create([
        'name' => 'Requirement 2',
        'hyperlink' => 'https://example.com/req2',
        'description' => 'Description for requirement 2',
    ]);

    // Get the use requirements for the venue
    $results = $this->venueService->getUseRequirements($venue->id);

    // Assertions
    expect($results)->toHaveCount(2)
        ->and($results->pluck('name'))->toContain($requirement1->name, $requirement2->name);
});

it('throws InvalidArgumentException for negative id', function () {
    // Expect an InvalidArgumentException to be thrown
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The id must be greater than 0.');

    // Call the service with an invalid negative id
    $this->venueService->getUseRequirements(-1);
});

it('throws ModelNotFoundException for non-existent venue', function () {
    // Expect a ModelNotFoundException to be thrown
    $this->expectException(ModelNotFoundException::class);

    // Try to fetch requirements for a non-existent venue id
    $this->venueService->getUseRequirements(99999); // Assuming 99999 is non-existent
});

it('returns empty collection when venue has no use requirements', function () {
    // Create a venue without any requirements
    $venue = Venue::factory()->create();

    // Get the use requirements for the venue
    $results = $this->venueService->getUseRequirements($venue->id);

    // Assertions
    expect($results)->toHaveCount(0);
});
