<?php

use App\Models\Department;
use App\Models\Venue;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    // Mock dependencies
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService
    );
});

it('returns paginated venues without filters', function () {
    // Arrange
    Venue::factory()->count(3)->create();

    // Act
    $result = $this->venueService->getAllVenues();

    // Assert
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(3);
});

it('filters venues by name', function () {
    // Arrange
    Venue::factory()->create(['name' => 'Main Hall']);
    Venue::factory()->create(['name' => 'Side Room']);

    // Act
    $result = $this->venueService->getAllVenues(['name' => 'Main']);

    // Assert
    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Main Hall');
});

it('throws exception when invalid filter key is passed', function () {
    // Act + Assert
    $this->venueService->getAllVenues(['invalid_key' => 'value']);
})->throws(InvalidArgumentException::class, 'Invalid attribute keys detected: invalid_key');

it('throws exception when null value is passed', function () {
    $this->venueService->getAllVenues(['name' => null]);
})->throws(InvalidArgumentException::class, 'Null values are not allowed for keys: name');

it('filters by department_id and capacity', function () {
    $d1 = Department::factory()->create();
    $d2 = Department::factory()->create();

    $v1 = Venue::factory()->create(['department_id' => $d1->id, 'capacity' => 50]);
    $v2 = Venue::factory()->create(['department_id' => $d2->id, 'capacity' => 100]);

    $result = $this->venueService->getAllVenues([
        'department_id' => $v2->department_id,
        'capacity' => 80,
    ]);

    expect($result->total())->toBe(1)
        ->and($result->first()->id)->toBe($v2->id);
});
