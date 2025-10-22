<?php

use App\Models\Department;
use App\Services\DepartmentService;

it('returns a department for a valid ID', function () {
    $department = Department::factory()->create();

    $result = DepartmentService::getDepartmentByID($department->id);

    expect($result)->toBeInstanceOf(Department::class)
        ->and($result->id)->toBe($department->id);
});

it('throws InvalidArgumentException for negative ID', function () {
    DepartmentService::getDepartmentByID(-1);
})->throws(InvalidArgumentException::class);
