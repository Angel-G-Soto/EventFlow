<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Role;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->service = new VenueService(new DepartmentService());
});

it('soft deletes valid venues when user is system-administrator', function () {
    $admin = User::factory()->create();
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $admin->roles()->attach($role);

    $venues = Venue::factory()->count(3)->create();

    $this->service->deactivateVenues($venues->all(), $admin);

    foreach ($venues as $venue) {
        expect($venue->fresh()->deleted_at)->not->toBeNull();
    }
});

it('throws exception if user is not system-administrator', function () {
    $user = User::factory()->create(); // no role
    $venues = Venue::factory()->count(2)->create();

    $this->service->deactivateVenues($venues->all(), $user);
})->throws(\InvalidArgumentException::class, 'The manager and the director must be system-administrator.');

it('throws exception if array contains non-venue elements', function () {
    $admin = User::factory()->create();
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $admin->roles()->attach($role);

    $venues = [Venue::factory()->create(), 'not-a-venue'];

    $this->service->deactivateVenues($venues, $admin);
})->throws(\InvalidArgumentException::class, 'List contains elements that are not venues.');



