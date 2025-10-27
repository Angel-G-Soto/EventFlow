<?php

use App\Models\UseRequirement;
use App\Services\UseRequirementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->useRequirementService = new UseRequirementService();
});

it('retrieves a use requirement by ID', function () {
    $requirement = UseRequirement::factory()->create();

    $result = $this->useRequirementService->getUseRequirementByID($requirement->id);

    expect($result)->toBeInstanceOf(UseRequirement::class)
        ->and($result->id)->toBe($requirement->id);
});

it('throws InvalidArgumentException when ID is negative in getUseRequirementByID', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('UseRequirement ID must be a positive integer.');

    $this->useRequirementService->getUseRequirementByID(-5);
});

it('throws ModelNotFoundException when record not found in getUseRequirementByID', function () {
    $this->expectException(ModelNotFoundException::class);

    $this->useRequirementService->getUseRequirementByID(99999);
});
