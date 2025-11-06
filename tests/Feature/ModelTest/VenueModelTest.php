<?php

use App\Models\Venue;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Models\Event;
use App\Models\UseRequirement;
use Illuminate\Support\Collection;

it('belongs to a department', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    expect($venue->department)->toBeInstanceOf(Department::class)
        ->and($venue->department->id)->toBe($department->id);
});

it('has a manager', function () {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    expect($venue->department->employees)
        ->toBeInstanceOf(Collection::class)
        ->and(count($venue->department->employees))->toBeGreaterThanOrEqual(1)
        ->and($venue->department->employees->first())->toBeInstanceOf(User::class);
});

it('has many event requests', function () {
    $venue = Venue::factory()->create();
    Event::factory()->count(3)->create(['venue_id' => $venue->id]);

    expect($venue->requests)->toHaveCount(3)
        ->each->toBeInstanceOf(Event::class);
});

it('has many requirements', function () {
    $venue = Venue::factory()->create();
    UseRequirement::factory()->count(2)->create(['venue_id' => $venue->id]);

    expect($venue->requirements)->toHaveCount(2)
        ->each->toBeInstanceOf(UseRequirement::class);
});

it('allows mass assignment of fillable fields', function () {
    $department = Department::factory()->create();
    $manager = User::factory()->create();

    $data = [
        'name' => 'SALON DE CLASES',
        'code' => 'AE-102',
        'features' => '1001',
        'capacity' => 50,
        'test_capacity' => 40,
        'opening_time' => '09:00',
        'closing_time' => '10:00',
        'department_id' => $department->id,
        //'manager_id' => $manager->id,
    ];

    $venue = Venue::create($data);

    foreach ($data as $key => $value) {
        expect($venue->{$key})->toEqual($value);
    }
});

/// METHODS

it('returns the department id', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create([
        'department_id' => $department->id,
    ]);

    expect($venue->getDepartmentID())->toBe($department->id);
});

it('returns enabled features', function () {
    $venue = Venue::factory()->create([
        'features' => '1010', // online and teaching enabled
    ]);

    expect($venue->getFeatures())->toEqual(['online', 'teaching']);
});

it('correctly checks if venue is open', function () {
    $venue = Venue::factory()->create([
        'opening_time' => '08:00:00',
        'closing_time' => '18:00:00',
    ]);

    $openTime = new DateTime('2025-10-29 10:00:00');
    $closedTime = new DateTime('2025-10-29 19:00:00');

    expect($venue->isOpenAt($openTime))->toBeTrue()
        ->and($venue->isOpenAt($closedTime))->toBeFalse();
});

it('handles overnight hours correctly', function () {
    $venue = Venue::factory()->create([
        'opening_time' => '20:00:00',
        'closing_time' => '06:00:00', // overnight
    ]);

    $nightTime = new DateTime('2025-10-29 22:00:00');
    $morningTime = new DateTime('2025-10-30 05:00:00');
    $dayTime = new DateTime('2025-10-29 15:00:00');

    expect($venue->isOpenAt($nightTime))->toBeTrue()
        ->and($venue->isOpenAt($morningTime))->toBeTrue()
        ->and($venue->isOpenAt($dayTime))->toBeFalse();
});

it('detects conflicts with existing events', function () {
    $venue = Venue::factory()->create();

    Event::factory()->create([
        'venue_id' => $venue->id,
        'status' => 'approved',
        'start_time' => '2025-10-29 10:00:00',
        'end_time' => '2025-10-29 12:00:00',
    ]);

    $conflictingStart = new DateTime('2025-10-29 11:00:00');
    $conflictingEnd = new DateTime('2025-10-29 13:00:00');

    $nonConflictingStart = new DateTime('2025-10-29 12:30:00');
    $nonConflictingEnd = new DateTime('2025-10-29 13:30:00');

    expect($venue->hasConflict($conflictingStart, $conflictingEnd))->toBeTrue()
        ->and($venue->hasConflict($nonConflictingStart, $nonConflictingEnd))->toBeFalse();
});

it('checks if venue is available', function () {
    $venue = Venue::factory()->create([
        'opening_time' => '08:00:00',
        'closing_time' => '18:00:00',
    ]);

    Event::factory()->create([
        'venue_id' => $venue->id,
        'status' => 'approved',
        'start_time' => '2025-10-29 10:00:00',
        'end_time' => '2025-10-29 12:00:00',
    ]);

    $availableStart = new DateTime('2025-10-29 08:00:00');
    $availableEnd = new DateTime('2025-10-29 09:00:00');

    $unavailableStart = new DateTime('2025-10-29 11:00:00');
    $unavailableEnd = new DateTime('2025-10-29 13:00:00');

    expect($venue->isAvailable($availableStart, $availableEnd))->toBeTrue()
        ->and($venue->isAvailable($unavailableStart, $unavailableEnd))->toBeFalse();
});
