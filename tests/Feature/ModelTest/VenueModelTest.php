<?php

use App\Models\Venue;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Models\Event;
use App\Models\UseRequirement;

it('belongs to a department', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    expect($venue->department)->toBeInstanceOf(Department::class)
        ->and($venue->department->id)->toBe($department->id);
});

it('has a manager', function () {
    $manager = User::factory()->create();
    $venue = Venue::factory()->create(['manager_id' => $manager->id]);

    expect($venue->manager)->toBeInstanceOf(User::class)
        ->and($venue->manager->id)->toBe($manager->id);
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
        'manager_id' => $manager->id,
    ];

    $venue = Venue::create($data);

    foreach ($data as $key => $value) {
        expect($venue->{$key})->toEqual($value);
    }
});
