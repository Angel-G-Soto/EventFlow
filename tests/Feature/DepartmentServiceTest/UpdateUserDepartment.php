<?php

use App\Models\User;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('assigns a department to a user', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => null]);

    $user = DepartmentService::updateUserDepartment($department, $user);

    expect($user->fresh()->department_id)->toBe($department->id);
});

it('throws an exception if department is not persisted', function () {
    $department = new Department();
    $user = User::factory()->create();

    DepartmentService::updateUserDepartment($department, $user);
})->throws(ModelNotFoundException::class, "Either the department or the user does not exist in the database.");

it('throws an exception if user is not persisted', function () {
    $department = Department::factory()->create();
    $user = new User();

    DepartmentService::updateUserDepartment($department, $user);
})->throws(ModelNotFoundException::class, "Either the department or the user does not exist in the database.");
