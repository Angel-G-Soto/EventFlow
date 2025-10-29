<?php

use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('successfully updates a user department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => null]);

    // Mock the userService to return the user
    $this->userService
        ->shouldReceive('findUserById')
        ->once()
        ->with($user->id)
        ->andReturn($user);

    $updatedUser = $this->departmentService->updateUserDepartment($department, $user);

    expect($updatedUser)->toBeInstanceOf(User::class)
        ->and($updatedUser->department_id)->toBe($department->id)
        ->and(User::find($user->id)->department_id)->toBe($department->id);
});

it('throws ModelNotFoundException if the department does not exist', function () {
    $department = Department::factory()->make(['id' => 999]);
    $user = User::factory()->create();

    $this->expectException(ModelNotFoundException::class);
    $this->expectExceptionMessage('Either the department or the user does not exist in the database.');

    $this->departmentService->updateUserDepartment($department, $user);
});

it('throws ModelNotFoundException if the user does not exist', function () {
    $department = Department::factory()->create();
    $user = User::factory()->make(['id' => 999]);

    // Mock the userService to return null for non-existent user
    $this->userService
        ->shouldReceive('findUserById')
        ->once()
        ->with($user->id)
        ->andReturn(null);

    $this->expectException(ModelNotFoundException::class);
    $this->expectExceptionMessage('Either the department or the user does not exist in the database.');

    $this->departmentService->updateUserDepartment($department, $user);
});
