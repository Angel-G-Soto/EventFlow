<?php

use App\Models\User;
use App\Models\Department;
use App\Services\UserService;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('returns employees from the same department as the director', function () {
    $department = Department::factory()->create();

    // Director
    $director = User::factory()->create(['department_id' => $department->id]);

    // Employees in the same department
    $employees = User::factory()->count(3)->create(['department_id' => $department->id]);

    // Mock userService to return the director
    $this->userService
        ->shouldReceive('findUserById')
        ->with($director->id)
        ->andReturn($director);

    $result = $this->departmentService->getEmployees($director->id);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($result->every(fn($emp) => $emp->department_id === $department->id))->toBeTrue();
});

it('throws exception if director id is negative', function () {
    $this->departmentService->getEmployees(-1);
})->throws(InvalidArgumentException::class, 'Id must be greater than zero.');

it('throws exception if director has no department', function () {
    $director = User::factory()->create(['department_id' => null]);

    $this->userService
        ->shouldReceive('findUserById')
        ->with($director->id)
        ->andReturn($director);

    $this->departmentService->getEmployees($director->id);
})->throws(InvalidArgumentException::class, 'Director is not part of any department.');
