<?php

use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('returns all departments as a collection', function () {
    // Arrange: create some departments
    Department::factory()->count(3)->create();

    // Act
    $result = $this->departmentService->getAllDepartments();

    // Assert
    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(3);
});

it('returns an empty collection when there are no departments', function () {
    // Act
    $result = $this->departmentService->getAllDepartments();

    // Assert
    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->and($result)->toBeEmpty();
});
