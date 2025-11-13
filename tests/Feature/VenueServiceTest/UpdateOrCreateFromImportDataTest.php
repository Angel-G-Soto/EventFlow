<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\AuditService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->auditService = Mockery::mock(AuditService::class);
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService,
    );

    $this->admin = Mockery::mock(User::class)->makePartial();
    $this->admin->id = 1;
    $this->admin->name = 'Admin User';
});


it('creates or updates venues successfully from valid import data', function () {
    $department = Department::factory()->create(['name' => 'ADEM']);
    $venueData = [
        [
            'name' => 'SALON DE CLASES',
            'code' => 'AE-102',
            'department' => 'ADEM',
            'features' => '0101',
            'capacity' => 50,
            'test_capacity' => 40,
        ],
        [
            'name' => 'LABORATORIO',
            'code' => 'AE-103',
            'department' => 'ADEM',
            'features' => '0110',
            'capacity' => 30,
            'test_capacity' => 20,
        ],
    ];

    $this->departmentService
        ->shouldReceive('findByName')
        ->twice()
        ->with('ADEM')
        ->andReturn($department);

    $this->auditService
        ->shouldReceive('logAdminAction')
        ->once()
        //->with($this->admin->id, '', '', 'Updated venues from import data.')
        ->andReturn(Mockery::mock(\App\Models\AuditTrail::class));

    $result = $this->venueService->updateOrCreateFromImportData($venueData, $this->admin);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and(Venue::count())->toBe(2);
});


it('throws exception when venue data contains invalid keys', function () {
    $venueData = [
        [
            'name' => 'Invalid Venue',
            'code' => 'AE-001',
            'department' => 'ADEM',
            'wrong_key' => 'unexpected',
        ]
    ];

    $this->venueService->updateOrCreateFromImportData($venueData, $this->admin);
})->throws(InvalidArgumentException::class, 'Invalid attribute keys detected');


it('throws exception when any venue data contains null values', function () {
    $venueData = [
        [
            'name' => null,
            'code' => 'AE-002',
            'department' => 'ADEM',
            'features' => '0101',
            'capacity' => 50,
            'test_capacity' => 40,
        ]
    ];

    $this->venueService->updateOrCreateFromImportData($venueData, $this->admin);
})->throws(InvalidArgumentException::class, 'Null values are not allowed for keys: name');


it('throws exception when department does not exist', function () {
    $venueData = [
        [
            'name' => 'CLASSROOM 101',
            'code' => 'AE-104',
            'department' => 'NON_EXISTENT',
            'features' => '1111',
            'capacity' => 60,
            'test_capacity' => 50,
        ]
    ];

    $this->departmentService
        ->shouldReceive('findByName')
        ->once()
        ->with('NON_EXISTENT')
        ->andReturn(null);

    $this->venueService->updateOrCreateFromImportData($venueData, $this->admin);
})->throws(ModelNotFoundException::class, 'Department [NON_EXISTENT] does not exist.');
