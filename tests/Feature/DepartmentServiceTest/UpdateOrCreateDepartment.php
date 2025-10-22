<?php

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Support\Collection;

it('creates new departments from valid data', function () {
    $data = [
        ['d_name' => 'Mechanical Engineering', 'd_code' => '123'],
        ['d_name' => 'Finance', 'd_code' => '456'],
    ];

    $result = DepartmentService::updateOrCreateDepartment($data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and(Department::count())->toBe(2);
});

it('updates existing departments if they already exist', function () {
    Department::create(['d_name' => 'Mechanical Engineering', 'd_code' => '123']);

    $data = [
        ['d_name' => 'Mechanical Engineering', 'd_code' => '123'],
    ];

    $result = DepartmentService::updateOrCreateDepartment($data);

    expect($result)->toHaveCount(1)
        ->and(Department::count())->toBe(1);
});

it('throws an exception if data is missing keys', function () {
    $data = [
        ['d_name' => 'Mechanical Engineering'],
    ];

    DepartmentService::updateOrCreateDepartment($data);
})->throws(Exception::class, 'Unable to synchronize department data.');
