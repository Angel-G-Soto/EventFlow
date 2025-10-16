<?php

use App\Models\Event;
use App\Models\Venue;
use App\Models\Department;
use App\Services\VenueService;
use Illuminate\Support\Collection;

it('creates or updates venues from import data', function () {

    $department = Department::factory()->create([
        'd_name' => 'Engineering',
        'd_code' => '123',
    ]);

    $venueData = [
        [
            'v_name' => 'SALON DE CLASES',
            'v_code' => 'AE-102',
            'v_department' => 'Engineering',
            'v_features' => 1001,
            'v_capacity' => 120,
            'v_test_capacity' => 80,
        ],
        [
            'v_name' => 'SALON DE CONFERENCIA',
            'v_code' => 'AE-115',
            'v_department' => 'Engineering',
            'v_features' => 1100,
            'v_capacity' => 100,
            'v_test_capacity' => 70,
        ],
    ];

    $result = VenueService::updateOrCreateFromImportData($venueData);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and(
            Venue::where('v_name', 'SALON DE CLASES')
                ->where('v_code', 'AE-102')
                ->where('department_id', $department->id)
                ->exists()
        )->toBeTrue()
        ->and(
            Venue::where('v_name', 'SALON DE CONFERENCIA')
                ->where('v_code', 'AE-115')
                ->where('department_id', $department->id)
                ->exists()
        )->toBeTrue();

});

it('throws exception if department does not exist', function () {
    $venueData = [
        [
            'v_name' => 'SALON DE CLASES',
            'v_code' => 'AE-106',
            'v_department' => 'NonExistentDept',
            'v_features' => '1001',
            'v_capacity' => 50,
            'v_test_capacity' => 40,
        ],
    ];

    VenueService::updateOrCreateFromImportData($venueData);
})->throws(\Exception::class, 'Unable to synchronize venue data.');
