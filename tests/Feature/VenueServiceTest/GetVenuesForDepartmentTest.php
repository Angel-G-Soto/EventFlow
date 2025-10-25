<?php

use App\Models\Venue;
use App\Models\Department;
use App\Services\VenueService;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->venueService = new VenueService(new DepartmentService());
});

it('returns venues for a department', function () {
    // Create a department dynamically.
    $department = Department::factory()->create();

    // Create two venues that belong to the department and one venue that does not.
    $venue1 = Venue::factory()->create(['department_id' => $department->id]);
    $venue2 = Venue::factory()->create(['department_id' => $department->id]);
    Venue::factory()->create();  // This venue will not be associated with the department.

    // Retrieve the venues associated with the department dynamically.
    $results = $this->venueService->getVenuesForDepartment($department);

    // Assertions
    expect($results)->toHaveCount(2)
        ->and($results->pluck('id'))->toContain($venue1->id, $venue2->id);
});

it('returns empty collection when department has no venues', function () {
    // Create a department dynamically.
    $department = Department::factory()->create();

    // Retrieve the venues associated with the department dynamically.
    $results = $this->venueService->getVenuesForDepartment($department);

    // Assertions
    expect($results)->toBeEmpty();
});

