<?php

use App\Models\UseRequirement;
use App\Services\UseRequirementService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->useRequirementService = new UseRequirementService();
});

it('retrieves all use requirement categories', function () {
    $requirements = UseRequirement::factory(3)->create();

    $result = $this->useRequirementService->getAllCategories();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(3)
        ->and($result->pluck('id')->all())->toMatchArray($requirements->pluck('id')->all());
});

it('returns an empty collection if there are no use requirements', function () {
    $result = $this->useRequirementService->getAllCategories();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toBeEmpty();
});
