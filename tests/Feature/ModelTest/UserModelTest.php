<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Models\EventRequestHistory;
use App\Models\Event;

it('belongs to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    expect($user->department)->toBeInstanceOf(Department::class)
        ->and($user->department->id)->toBe($department->id);
});

it('has many managed venues', function () {
    $user = User::factory()->create();
    Venue::factory()->count(3)->create(['manager_id' => $user->id]);

    expect($user->manages)->toHaveCount(3)
        ->each->toBeInstanceOf(Venue::class);
});

it('has many request action logs', function () {
    $user = User::factory()->create();
    EventRequestHistory::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->requestActionLog)->toHaveCount(2)
        ->each->toBeInstanceOf(EventRequestHistory::class);
});

it('has many event requests', function () {
    $user = User::factory()->create();
    Event::factory()->count(4)->create(['creator_id' => $user->id]);

    expect($user->requests)->toHaveCount(4)
        ->each->toBeInstanceOf(Event::class);
});

it('allows mass assignment of fillable fields', function () {
    $department = Department::factory()->create();
    $user = User::create([
        'department_id' => $department->id,
        'email' => 'harry.potter@hogwarts.com',
        'password' => bcrypt('password'),
        'first_name' => 'Harry',
        'last_name' => 'Potter',
        'auth_type' => 'sso',
    ]);

    expect($user->email)->toBe('harry.potter@hogwarts.com')
        ->and($user->first_name)->toBe('Harry')
        ->and($user->last_name)->toBe('Potter')
        ->and($user->auth_type)->toBe('sso');
});
