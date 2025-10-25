<?php

use App\Models\Venue;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->service = new VenueService(new DepartmentService());
});

it('returns a venue by valid ID', function () {
    $venue = Venue::factory()->create();

    $result = $this->service->getVenueById($venue->id);

    expect($result)->not()->toBeNull()
        ->and($result->id)->toBe($venue->id)
        ->and($result->v_name)->toBe($venue->v_name);
});

it('returns null if venue does not exist', function () {
    $nonExistingId = 999;

    $result = $this->service->getVenueById($nonExistingId);

    expect($result)->toBeNull();
});

it('throws InvalidArgumentException if ID is negative', function () {
    $this->service->getVenueById(-1);
})->throws(InvalidArgumentException::class, 'Venue id must be greater than 0.');
