<?php

use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('returns the department if it exists by name', function () {
    $department = Department::factory()->create(['name' => 'Finance']);

    $result = $this->departmentService->findByName('Finance');

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($department->id)
        ->and($result->name)->toBe('Finance');
});

it('returns null if no department exists with that name', function () {
    $result = $this->departmentService->findByName('NonExistentDepartment');

    expect($result)->toBeNull();
});
