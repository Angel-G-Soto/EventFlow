<?php

use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->userService = Mockery::mock(UserService::class);
    $this->departmentService = new DepartmentService($this->userService);
});

it('creates new departments from valid data', function () {
    $data = [
        ['name' => 'Mechanical Engineering', 'code' => '123'],
        ['name' => 'Finance', 'code' => '456'],
    ];

    $result = $this->departmentService->updateOrCreateDepartment($data);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and(Department::count())->toBe(2)
        ->and(Department::where('name', 'Mechanical Engineering')->exists())->toBeTrue()
        ->and(Department::where('name', 'Finance')->exists())->toBeTrue();

});

it('updates existing departments if they already exist', function () {
    // Arrange
    $department = Department::factory()->create([
        'name' => 'Finance',
        'code' => '456',
    ]);

    $data = [
        ['name' => 'Finance', 'code' => '456'], // same name/code → should update not duplicate
    ];

    $result = $this->departmentService->updateOrCreateDepartment($data);

    expect($result->count())->toBe(1)
        ->and(Department::count())->toBe(1)
        ->and($result->first()->name)->toBe('Finance');
});

it('throws InvalidArgumentException when name or code is missing', function () {
    $data = [
        ['name' => 'Mechanical Engineering'], // missing code
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Missing required keys 'name' or 'code' in department at index 0.");

    $this->departmentService->updateOrCreateDepartment($data);
});

it('throws InvalidArgumentException when name or code are not strings', function () {
    $data = [
        ['name' => 123, 'code' => true],
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid data types in department at index 0. Both 'name' and 'code' must be strings.");

    $this->departmentService->updateOrCreateDepartment($data);
});
