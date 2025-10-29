<?php

use App\Models\UseRequirement;
use App\Services\UseRequirementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new UseRequirementService();
});

it('updates a use requirement successfully', function () {
    // Arrange
    $requirement = UseRequirement::factory()->create([
        'name' => 'name',
        'hyperlink' => 'example.test',
        'description' => 'description',
    ]);

    // Act
    $updated = $this->service->updateUseRequirement(
        $requirement->id,
        'new name',
        'new-example.test',
        'new description'
    );

    expect($updated)->toBeInstanceOf(UseRequirement::class)
        ->and($updated->id)->toEqual($requirement->id)
        ->and($updated->name)->toEqual('new name')
        ->and($updated->hyperlink)->toEqual('new-example.test')
        ->and($updated->description)->toEqual('new description');

    $this->assertDatabaseHas('use_requirements', [
        'id' => $requirement->id,
        'name' => 'new name',
        'hyperlink' => 'new-example.test',
        'description' => 'new description',
    ]);
});

it('throws an exception if id is negative', function () {
    $this->service->updateUseRequirement(-1, 'Name', 'link', 'desc');
})->throws(InvalidArgumentException::class, 'UseRequirement ID must be a positive integer.');

it('throws ModelNotFoundException if requirement does not exist', function () {
    $this->service->updateUseRequirement(9999, 'Name', 'link', 'desc');
})->throws(ModelNotFoundException::class);
