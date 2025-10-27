<?php

use App\Models\UseRequirement;
use App\Services\UseRequirementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->useRequirementService = new UseRequirementService();
});

it('updates an existing use requirement name', function () {
    $requirement = UseRequirement::factory()->create(['name' => 'Old Name']);

    $updated = $this->useRequirementService->updateUseRequirement($requirement->id, 'New Name');

    expect($updated)->toBeInstanceOf(UseRequirement::class)
        ->and($updated->name)->toBe('New Name')
        ->and(UseRequirement::find($requirement->id)->name)->toBe('New Name');
});

it('throws InvalidArgumentException for negative ID in updateUseRequirement', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('UseRequirement ID must be a positive integer.');

    $this->useRequirementService->updateUseRequirement(-2, 'Updated Name');
});

it('throws ModelNotFoundException if record does not exist in updateUseRequirement', function () {
    $this->expectException(ModelNotFoundException::class);

    $this->useRequirementService->updateUseRequirement(99999, 'Updated Name');
});
