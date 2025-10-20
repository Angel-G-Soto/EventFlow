<?php

use App\Models\Department;
use App\Models\Venue;
use App\Models\User;
use App\Models\UseRequirement;

it('has many venues', function () {
    $department = Department::factory()->create();
    Venue::factory()->count(3)->create(['department_id' => $department->id]);

    expect($department->venues)->toHaveCount(3)
        ->each->toBeInstanceOf(Venue::class);
});

it('has many managers', function () {
    $department = Department::factory()->create();
    User::factory()->count(2)->create(['department_id' => $department->id]);

    expect($department->managers)->toHaveCount(2)
        ->each->toBeInstanceOf(User::class);
});

it('has many requirements', function () {
    $department = Department::factory()->create();
    UseRequirement::factory()->count(2)->create(['department_id' => $department->id]);

    expect($department->requirements)->toHaveCount(2)
        ->each->toBeInstanceOf(UseRequirement::class);
});

it('allows mass assignment of fillable fields', function () {
    $data = [
        'd_name' => 'Computer Science',
        'd_code' => 'CS',
    ];

    $department = Department::create($data);

    foreach ($data as $key => $value) {
        expect($department->{$key})->toEqual($value);
    }
});
