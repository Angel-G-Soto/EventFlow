<?php

use App\Models\Event;
use App\Models\Venue;
use App\Services\VenueService;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;

it('assigns manager to venue department successfully', function () {

    $department = Department::factory()->create();
    $venue = Venue::factory()->create([
        'department_id' => $department->id,
    ]);

    $manager = User::factory()->create();
    $admin = User::factory()->create();

    //Assign roles

    VenueService::assignManager($venue, $manager, $admin);

    $manager->refresh();
    expect($manager->department_id)->toBe($venue->department_id);
});

