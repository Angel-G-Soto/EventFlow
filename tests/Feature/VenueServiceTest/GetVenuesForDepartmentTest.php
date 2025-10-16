<?php

use App\Models\Venue;
use App\Models\Department;
use App\Services\VenueService;

it('returns venues for a department', function () {
    $department = Department::factory()->create();

    $venue1 = Venue::factory()->create(['department_id' => $department->id]);
    $venue2 = Venue::factory()->create(['department_id' => $department->id]);
    Venue::factory()->create();

    $results = VenueService::getVenuesForDepartment($department);

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id'))->toContain($venue1->id, $venue2->id);
});

it('returns empty collection when department has no venues', function () {
    $department = Department::factory()->create();

    $results = VenueService::getVenuesForDepartment($department);

    expect($results)->toBeEmpty();
});
