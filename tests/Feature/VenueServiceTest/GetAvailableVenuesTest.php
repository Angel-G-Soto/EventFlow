<?php

use App\Models\Venue;
use App\Models\Event;
use App\Models\User;
use App\Models\Department;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService,
    );

    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
});


it('throws exception when start time is not before end time', function () {
    $start = new DateTime('2025-01-02 14:00:00');
    $end = new DateTime('2025-01-02 10:00:00');

    $this->venueService->getAvailableVenues($start, $end);
})->throws(InvalidArgumentException::class, 'Start time must be before end time.');


it('returns all venues when no events exist', function () {
    $v1 = Venue::factory()->create(['department_id' => $this->department->id]);
    $v2 = Venue::factory()->create(['department_id' => $this->department->id]);

    $start = new DateTime('2025-01-02 08:00:00');
    $end = new DateTime('2025-01-02 18:00:00');

    $result = $this->venueService->getAvailableVenues($start, $end);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->pluck('id'))->toContain($v1->id, $v2->id);
});


it('excludes venues with approved overlapping events', function () {
    $v1 = Venue::factory()->create(['department_id' => $this->department->id]);
    $v2 = Venue::factory()->create(['department_id' => $this->department->id]);

    Event::factory()->create([
        'venue_id' => $v1->id,
        'status' => 'approved',
        'start_time' => '2025-01-02 09:00:00',
        'end_time' => '2025-01-02 12:00:00',
    ]);

    $start = new DateTime('2025-01-02 08:00:00');
    $end = new DateTime('2025-01-02 13:00:00');

    $result = $this->venueService->getAvailableVenues($start, $end);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->pluck('id'))->not->toContain($v1->id)
        ->and($result->pluck('id'))->toContain($v2->id);
});


it('includes venues with non-overlapping or unapproved events', function () {
    $v1 = Venue::factory()->create(['department_id' => $this->department->id]);
    $v2 = Venue::factory()->create(['department_id' => $this->department->id]);

    // Unapproved event (should NOT exclude v1)
    Event::factory()->create([
        'venue_id' => $v1->id,
        'status' => 'pending',
        'start_time' => '2025-01-02 09:00:00',
        'end_time' => '2025-01-02 11:00:00',
    ]);

    // Approved but non-overlapping event (should NOT exclude v2)
    Event::factory()->create([
        'venue_id' => $v2->id,
        'status' => 'approved',
        'start_time' => '2025-01-02 20:00:00',
        'end_time' => '2025-01-02 22:00:00',
    ]);

    $start = new DateTime('2025-01-02 08:00:00');
    $end = new DateTime('2025-01-02 12:00:00');

    $result = $this->venueService->getAvailableVenues($start, $end);

    expect($result->pluck('id'))->toContain($v1->id, $v2->id);
});
