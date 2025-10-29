<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\UseRequirement;
use App\Services\DepartmentService;
use App\Services\UserService;
use App\Services\VenueService;
use App\Services\AuditService;
use App\Services\UseRequirementService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

beforeEach(function () {
    // Mock dependent services
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

    $this->venue = Venue::factory()->create();

    // Mock manager
    $this->manager = Mockery::mock(User::class)->makePartial();
    $this->manager->id = 10;
    $this->manager->name = 'Test Manager';
    $this->manager->department_id = $this->venue->department_id;

    // Default: manager belongs to venue department
    $departmentMock = Mockery::mock(BelongsTo::class);
    $departmentMock->shouldReceive('where')
        ->with('id', $this->venue->department_id)
        ->andReturnSelf();
    $departmentMock->shouldReceive('first')
        ->andReturn((object)['id' => $this->venue->department_id]);

    $this->manager->shouldReceive('department')->andReturn($departmentMock);
});

it('creates venue requirements successfully', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $requirements = [
        ['name' => 'Safety Policy', 'hyperlink' => 'https://example.com/safety', 'description' => 'Safety documentation'],
        ['name' => 'Usage Terms', 'hyperlink' => 'https://example.com/terms', 'description' => 'Usage terms and conditions']
    ];

    // Expect dependencies to be called
    $this->useRequirementService->shouldReceive('deleteUseRequirement')->once()->with($this->venue->id);
    $this->auditService->shouldReceive('logAction')->times(2)->andReturn(Mockery::mock(\App\Models\AuditTrail::class));

    // Mock UseRequirement to prevent actual DB write
    $mockRequirement = Mockery::mock('overload:' . UseRequirement::class);
    $mockRequirement->shouldReceive('save')->andReturnTrue();

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
    expect(true)->toBeTrue(); // If no exception is thrown, success
});

it('throws exception if manager does not have venue-manager role', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['random-role']));

    $requirements = [
        ['name' => 'Safety', 'hyperlink' => 'https://example.com', 'description' => 'desc']
    ];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(InvalidArgumentException::class, 'Manager does not have the required role.');

it('throws exception if manager not in venue department', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $departmentMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    $departmentMock->shouldReceive('where')
        ->with('id', $this->venue->department_id)
        ->andReturnSelf();
    $departmentMock->shouldReceive('first')
        ->andReturn(null); // Simulate manager not in department

    $this->manager->shouldReceive('department')->andReturn($departmentMock);

    $requirements = [
        ['name' => 'Safety', 'hyperlink' => 'https://example.com', 'description' => 'desc']
    ];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(InvalidArgumentException::class, 'Manager does not belong to the venue department.');


it('throws exception if requirement is missing keys', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $requirements = [
        ['name' => 'Incomplete Requirement', 'hyperlink' => 'https://example.com'] // Missing 'description'
    ];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(InvalidArgumentException::class, 'Missing keys in requirement at index 0: description');

it('throws exception if requirement field is null', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $requirements = [
        ['name' => null, 'hyperlink' => 'https://example.com', 'description' => 'desc']
    ];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(InvalidArgumentException::class, "The field 'name' in requirement at index 0 cannot be null.");
