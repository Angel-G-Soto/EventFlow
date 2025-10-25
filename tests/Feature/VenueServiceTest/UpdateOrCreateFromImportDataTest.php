<?php

use App\Models\Event;
use App\Models\Venue;
use App\Models\Department;
use App\Services\VenueService;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

beforeEach(function () {
    $departmentService = new DepartmentService();
    $this->service = new VenueService($departmentService);
});


it('creates or updates venues from import data', function () {

    $department = Department::factory()->create([
        'name' => 'Engineering',
        'code' => '123',
    ]);

    $venueData = [
        [
            'name' => 'SALON DE CLASES',
            'code' => 'AE-102',
            'department' => 'Engineering',
            'features' => 1001,
            'capacity' => 120,
            'test_capacity' => 80,
        ],
        [
            'name' => 'SALON DE CONFERENCIA',
            'code' => 'AE-115',
            'department' => 'Engineering',
            'features' => 1100,
            'capacity' => 100,
            'test_capacity' => 70,
        ],
    ];

    $result = $this->service->updateOrCreateFromImportData($venueData);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and(
            Venue::where('name', 'SALON DE CLASES')
                ->where('code', 'AE-102')
                ->where('department_id', $department->id)
                ->exists()
        )->toBeTrue()
        ->and(
            Venue::where('name', 'SALON DE CONFERENCIA')
                ->where('code', 'AE-115')
                ->where('department_id', $department->id)
                ->exists()
        )->toBeTrue();

});

it('throws exception if department does not exist', function () {
    $venueData = [
        [
            'name' => 'SALON DE CLASES',
            'code' => 'AE-106',
            'department' => 'NonExistentDept',
            'features' => '1001',
            'capacity' => 50,
            'test_capacity' => 40,
        ],
    ];

    $this->service->updateOrCreateFromImportData($venueData);
})->throws(ModelNotFoundException::class);


it('throws InvalidArgumentException if venue array contains invalid keys', function () {
    $venueData = [
        [
            'name' => 'SALON DE CLASES',
            'code' => 'AE-102',
            'department' => 'Empresas',
            'features' => '1000',
            'capacity' => 50,
            'test_capacity' => 40,
            'invalid_key' => 'invalid', // Invalid key
        ]
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid attribute keys detected: invalid_key');

    $this->service->updateOrCreateFromImportData($venueData);
});

it('throws InvalidArgumentException if venue array contains null values', function () {
    $venueData = [
        [
            'name' => null, // Null value
            'code' => 'R101',
            'department' => 'CSE',
            'features' => 'multimedia',
            'capacity' => 50,
            'test_capacity' => 40,
        ]
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Null values are not allowed for keys: name");

    $this->service->updateOrCreateFromImportData($venueData);
});
