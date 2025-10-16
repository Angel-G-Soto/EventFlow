<?php

use App\Models\Event;
use App\Models\Venue;
use App\Services\VenueService;

it('returns only venues not in approved events within time range', function () {

    $venue1 = Venue::factory()->create();
    $venue2 = Venue::factory()->create();
    $venue3 = Venue::factory()->create();

    Event::factory()->create([
        'venue_id' => $venue1->id,
        'e_start_time' => now()->addHour(),
        'e_end_time' => now()->addHours(2),
        'e_status' => 'Pending - Advisor',
    ]);

    Event::factory()->create([
        'venue_id' => $venue2->id,
        'e_start_time' => now()->addHour(),
        'e_end_time' => now()->addHours(2),
        'e_status' => 'Approved',
    ]);

    $start = now();
    $end = now()->addHours(3);

    $availableVenues = VenueService::getAvailableVenues($start, $end);

    expect($availableVenues->pluck('id'))
        ->toContain($venue2->id)
        ->toContain($venue3->id)
        ->not->toContain($venue1->id);
});

it('returns all venues if no events conflict in time range', function () {
    $venue1 = Venue::factory()->create();
    $venue2 = Venue::factory()->create();

    $start = now();
    $end = now()->addHours(2);

    // No events are created

    $availableVenues = VenueService::getAvailableVenues($start, $end);

    expect($availableVenues->pluck('id'))
        ->toContain($venue1->id)
        ->toContain($venue2->id);
});

it('throws an exception if start time is after or equal to end time', function () {
    $start = now()->addHour();
    $end = now();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Start time must be before end time.');

    VenueService::getAvailableVenues($start, $end);
});
