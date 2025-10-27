<?php

use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;


beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('deletes a department successfully', function () {
    $department = Department::factory()->create();

    $result = $this->departmentService->deleteDepartment($department->id);

    expect($result)->toBeTrue()
        ->and(Department::find($department->id))->toBeNull();
});

it('throws InvalidArgumentException for negative ids', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Department ID must be a positive integer.');

    $this->departmentService->deleteDepartment(-5);
});

it('throws ModelNotFoundException if department does not exist', function () {
    $this->expectException(ModelNotFoundException::class);

    $this->departmentService->deleteDepartment(99999);
});
