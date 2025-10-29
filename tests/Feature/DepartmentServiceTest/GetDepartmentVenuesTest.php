<?php

use App\Models\Department;
use App\Models\Venue;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('returns venues for an existing department', function () {
    $department = Department::factory()->create();
    $venues = Venue::factory(3)->create(['department_id' => $department->id]);

    $result = $this->departmentService->getDepartmentVenues($department);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(3)
        ->and($result->pluck('id')->all())->toMatchArray($venues->pluck('id')->all());
});

it('throws ModelNotFoundException if department does not exist', function () {
    $nonExistentDepartmentId = 99999; // make sure this ID is not in DB
    $fakeDepartment = new Department(['id' => $nonExistentDepartmentId]);

    $this->expectException(ModelNotFoundException::class);

    $this->departmentService->getDepartmentVenues($fakeDepartment);
});
