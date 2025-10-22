<?php

use App\Models\Department;
use App\Models\UseRequirement;
use App\Services\DepartmentService;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('returns requirements for a valid department ID', function () {
    $department = Department::factory()->create();
    UseRequirement::factory()->count(3)->create(['department_id' => $department->id]);

    $requirements = DepartmentService::getUseRequirement($department->id);

    expect($requirements)->toBeInstanceOf(Collection::class)
        ->and($requirements)->toHaveCount(3);
});

it('throws InvalidArgumentException for ID of 0', function () {
    DepartmentService::getUseRequirement(-1);
})->throws(InvalidArgumentException::class, 'Department ID must be a positive integer.');

it('throws Exception when department is not found', function () {
    DepartmentService::getUseRequirement(999);
})->throws(ModelNotFoundException::class);

