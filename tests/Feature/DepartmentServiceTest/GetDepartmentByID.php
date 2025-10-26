<?php

use App\Models\Department;
use App\Services\DepartmentService;

beforeEach(function () {
    $this->service = new DepartmentService();
});

it('returns a department for a valid ID', function () {
    $department = Department::factory()->create();

    $result = $this->service->getDepartmentByID($department->id);

    expect($result)->toBeInstanceOf(Department::class)
        ->and($result->id)->toBe($department->id);
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->service->getDepartmentByID(-1);
})->throws(InvalidArgumentException::class);
