<?php

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->service = new DepartmentService();
});

it('returns a collection of all departments', function () {
    Department::factory()->count(3)->create();

    $departments = $this->service->getAllDepartments();

    expect($departments)->toBeInstanceOf(Collection::class)
        ->and($departments)->toHaveCount(3);
});

it('returns an empty collection if no departments exist', function () {
    $departments = $this->service->getAllDepartments();

    expect($departments)->toBeInstanceOf(Collection::class)
        ->and($departments)->toHaveCount(0);
});
