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

it('belongs to many categories', function () {
    $venue = Venue::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $venue->categories()->attach($categories->pluck('id'));

    expect($venue->categories)->toHaveCount(2)
        ->each->toBeInstanceOf(Category::class);
});

it('allows mass assignment of fillable fields', function () {
    $department = Department::factory()->create();
    $manager = User::factory()->create();

    $data = [
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-102',
        'v_features' => '1001',
        'v_capacity' => 50,
        'v_test_capacity' => 40,
        'department_id' => $department->id,
        'manager_id' => $manager->id,
    ];

    $venue = Venue::create($data);

    foreach ($data as $key => $value) {
        expect($venue->{$key})->toEqual($value);
    }
});
