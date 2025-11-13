<?php

use App\Models\Department;
use App\Models\Venue;
use App\Models\User;
use App\Models\Role;

it('has many venues', function () {
    $department = Department::factory()->create();
    Venue::factory()->count(3)->create(['department_id' => $department->id]);

    expect($department->venues)->toHaveCount(3)
        ->each->toBeInstanceOf(Venue::class);
});

it('has many managers', function () {
    $department = Department::factory()->create();
    User::factory()->count(2)->create(['department_id' => $department->id]);

    expect($department->employees)->toHaveCount(2)
        ->each->toBeInstanceOf(User::class);
});

it('allows mass assignment of fillable fields', function () {
    $data = [
        'name' => 'Computer Science',
        'code' => '123',
    ];

    $department = Department::create($data);

    foreach ($data as $key => $value) {
        expect($department->{$key})->toEqual($value);
    }
});

it('returns the department director', function () {
    $department = Department::factory()->create();

    $directorRole = Role::factory()->create(['name' => 'department-director']);
    $staffRole = Role::factory()->create(['name' => 'staff']);

    $director = User::factory()->create();
    $staff = User::factory()->create();

    $department->employees()->saveMany([$director, $staff]);

    $director->roles()->attach($directorRole);
    $staff->roles()->attach($staffRole);

    $result = $department->getDirector();

    expect($result->id)->toBe($director->id);
});
