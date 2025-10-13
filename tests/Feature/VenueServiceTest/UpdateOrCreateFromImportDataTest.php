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
            'v_name' => 'Lecture Hall A',
            'v_code' => 'LHA001',
            'v_department' => 'Engineering',
            'v_features' => 1001,
            'v_capacity' => 120,
            'v_test_capacity' => 80,
        ],
        [
            'v_name' => 'Lecture Hall B',
            'v_code' => 'LHB001',
            'v_department' => 'Engineering',
            'v_features' => 1100,
            'v_capacity' => 100,
            'v_test_capacity' => 70,
        ],
    ];

    $result = VenueService::updateOrCreateFromImportData($venueData);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);

    $this->assertDatabaseHas('venues', [
        'v_name' => 'Lecture Hall A',
        'v_code' => 'LHA001',
        'department_id' => $department->id,
    ]);

    $this->assertDatabaseHas('venues', [
        'v_name' => 'Lecture Hall B',
        'v_code' => 'LHB001',
        'department_id' => $department->id,
    ]);
});

it('throws exception if department does not exist', function () {
    $venueData = [
        [
            'v_name' => 'Lecture Hall C',
            'v_code' => 'LHC001',
            'v_department' => 'NonExistentDept',
            'v_features' => 'TV',
            'v_capacity' => 50,
            'v_test_capacity' => 40,
        ],
    ];

    VenueService::updateOrCreateFromImportData($venueData);
})->throws(\Exception::class, 'Unable to synchronize venue data.');
