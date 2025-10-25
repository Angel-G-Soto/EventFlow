<?php

use App\Models\Event;
use App\Models\Venue;
use App\Services\VenueService;
use App\Services\DepartmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

beforeEach(function () {
    $departmentService = new DepartmentService();
    $this->service = new VenueService($departmentService);
});


it('throws an exception when start time is after or equal to end time', function () {
    $start = Carbon::create(2025, 10, 23, 12, 0, 0);
    $end   = Carbon::create(2025, 10, 23, 10, 0, 0);

    $this->service->getAvailableVenues($start, $end);
})->throws(InvalidArgumentException::class, 'Start time must be before end time.');

it('returns all venues when no approved events exist within the timeframe', function () {
    $venues = Venue::factory()->count(3)->create();

    $start = Carbon::create(2025, 10, 23, 8, 0, 0);
    $end   = Carbon::create(2025, 10, 23, 20, 0, 0);

    $available = $this->service->getAvailableVenues($start, $end);

    expect($available)->toHaveCount(3);
    expect($available->pluck('id')->sort()->values())->toEqual($venues->pluck('id')->sort()->values());
});

it('excludes venues that have approved events overlapping the timeframe', function () {
    $venueA = Venue::factory()->create();
    $venueB = Venue::factory()->create();
    $venueC = Venue::factory()->create();

    $start = Carbon::create(2025, 10, 23, 8, 0, 0);
    $end   = Carbon::create(2025, 10, 23, 20, 0, 0);

    // Event fully inside the timeframe (blocks venueA)
    Event::factory()->create([
        'venue_id'   => $venueA->id,
        'start_time' => Carbon::create(2025, 10, 23, 10, 0, 0),
        'end_time'   => Carbon::create(2025, 10, 23, 12, 0, 0),
        'status'     => 'approved',
    ]);

    // Event partially overlapping start of window (blocks venueB)
    Event::factory()->create([
        'venue_id'   => $venueB->id,
        'start_time' => Carbon::create(2025, 10, 23, 7, 0, 0),
        'end_time'   => Carbon::create(2025, 10, 23, 9, 0, 0),
        'status'     => 'approved',
    ]);

    // Event outside timeframe (venueC should be available)
    Event::factory()->create([
        'venue_id'   => $venueC->id,
        'start_time' => Carbon::create(2025, 10, 23, 22, 0, 0),
        'end_time'   => Carbon::create(2025, 10, 23, 23, 0, 0),
        'status'     => 'draft',
    ]);

    $available = $this->service->getAvailableVenues($start, $end);

    expect($available->pluck('id'))
        ->not->toContain($venueA->id)
        ->not->toContain($venueB->id)
        ->toContain($venueC->id);
});

it('includes venues that have unapproved or completed events within timeframe', function () {
    $venuePending = Venue::factory()->create();
    $venueCompleted = Venue::factory()->create();

    $start = Carbon::create(2025, 10, 23, 8, 0, 0);
    $end   = Carbon::create(2025, 10, 23, 20, 0, 0);

    Event::factory()->create([
        'venue_id'   => $venuePending->id,
        'start_time' => Carbon::create(2025, 10, 23, 9, 0, 0),
        'end_time'   => Carbon::create(2025, 10, 23, 11, 0, 0),
        'status'     => 'pending',
    ]);

    Event::factory()->create([
        'venue_id'   => $venueCompleted->id,
        'start_time' => Carbon::create(2025, 10, 23, 13, 0, 0),
        'end_time'   => Carbon::create(2025, 10, 23, 15, 0, 0),
        'status'     => 'completed',
    ]);

    $available = $this->service->getAvailableVenues($start, $end);

    expect($available->pluck('id'))
        ->toContain($venuePending->id)
        ->toContain($venueCompleted->id);
});

it('does not return soft-deleted venues', function () {
    $activeVenue = Venue::factory()->create(['deleted_at' => null]);
    $deletedVenue = Venue::factory()->create(['deleted_at' => Carbon::now()]);

    $start = Carbon::create(2025, 10, 23, 8, 0, 0);
    $end   = Carbon::create(2025, 10, 23, 20, 0, 0);

    $available = $this->service->getAvailableVenues($start, $end);

    expect($available->pluck('id'))
        ->toContain($activeVenue->id)
        ->not->toContain($deletedVenue->id);
});
