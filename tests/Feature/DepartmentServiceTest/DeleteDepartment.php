<?php

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('deletes a department successfully by ID', function () {
    $department = Department::factory()->create();

    $result = DepartmentService::deleteDepartment($department->id);

    expect($result)->toBeTrue();
    expect(Department::find($department->id))->toBeNull();
});

it('throws InvalidArgumentException for negative ID', function () {
    DepartmentService::deleteDepartment(-1);
})->throws(InvalidArgumentException::class, 'Department ID must be a positive integer.');

it('throws Exception if department does not exist', function () {
    DepartmentService::deleteDepartment(9999);
})->throws(ModelNotFoundException::class);
