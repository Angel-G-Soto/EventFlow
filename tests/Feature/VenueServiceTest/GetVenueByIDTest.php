<?php

use App\Models\Venue;
use App\Services\VenueService;

it('returns a venue by valid ID', function () {
    $venue = Venue::factory()->create();

    $result = VenueService::getVenueById($venue->id);

    expect($result)->not()->toBeNull()
        ->and($result->id)->toBe($venue->id)
        ->and($result->v_name)->toBe($venue->v_name);
});

it('returns null if venue does not exist', function () {
    $nonExistingId = 999;

    $result = VenueService::getVenueById($nonExistingId);

    expect($result)->toBeNull();
});

it('throws InvalidArgumentException if ID is negative', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Venue id must be greater than 0.');

    VenueService::getVenueById(-1);
});

