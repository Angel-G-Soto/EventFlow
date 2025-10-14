<?php

use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;

it('soft deletes all venues provided in the array', function () {
    $venues = Venue::factory()->count(3)->create();  // Create 3 venue instances

    VenueService::deactivateVenues($venues->all());

    foreach ($venues as $venue) {
        $venue->refresh();
        expect($venue->deleted_at)->not()->toBeNull();
    }
});

it('throws an exception when array contains non-venue elements', function () {
    $users = User::factory()->count(2)->create();

    expect(function () use ($users) {
        VenueService::deactivateVenues($users->all());
    })->toThrow(\InvalidArgumentException::class, 'List contains elements that are not venues.');
});

