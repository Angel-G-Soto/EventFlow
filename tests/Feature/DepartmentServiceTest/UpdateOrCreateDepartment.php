<?php

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->service = new DepartmentService();
});

it('creates new departments from valid data', function () {
    $data = [
        ['name' => 'Mechanical Engineering', 'code' => '123'],
        ['name' => 'Finance', 'code' => '456'],
    ];

    $result = $this->service->updateOrCreateDepartment($data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and(Department::count())->toBe(2);
});

it('updates existing departments if they already exist', function () {
    Department::create(['name' => 'Mechanical Engineering', 'code' => '123']);

    $data = [
        ['name' => 'Mechanical Engineering', 'code' => '123'],
    ];

    $result = $this->service->updateOrCreateDepartment($data);

    expect($result)->toHaveCount(1)
        ->and(Department::count())->toBe(1);
});

it('throws an exception if data is missing keys', function () {
    $data = [
        ['name' => 'Mechanical Engineering'],
    ];

    $this->service->updateOrCreateDepartment($data);
})->throws(Exception::class, 'Unable to synchronize department data.');
