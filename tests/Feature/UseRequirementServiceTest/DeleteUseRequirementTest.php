<?php

use App\Models\Venue;
use App\Models\UseRequirement;
use App\Services\UseRequirementService;

beforeEach(function () {
    $this->useRequirementService = new UseRequirementService();
});

it('deletes use requirements for a given venue ID', function () {
    $venue = Venue::factory()->create();
    $requirements = UseRequirement::factory(2)->create(['venue_id' => $venue->id]);

    $result = $this->useRequirementService->deleteVenueUseRequirements($venue->id);

    expect($result)->toBeTrue()
        ->and(UseRequirement::where('venue_id', $venue->id)->count())->toBe(0);
});

it('returns false when no use requirements exist for given venue ID', function () {
    $result = $this->useRequirementService->deleteVenueUseRequirements(999);

    expect($result)->toBeFalse();
});

it('throws InvalidArgumentException when venue ID is negative in deleteVenueUseRequirements', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('UseRequirement ID must be a positive integer.');

    $this->useRequirementService->deleteVenueUseRequirements(-10);
});
