<?php

use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('returns a department for a valid ID', function () {
    $department = Department::factory()->create();

    $result = $this->departmentService->getDepartmentByID($department->id);

    expect($result)->toBeInstanceOf(Department::class)
        ->and($result->id)->toBe($department->id);
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->departmentService->getDepartmentByID(-1);
})->throws(InvalidArgumentException::class);
