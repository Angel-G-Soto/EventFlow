<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Services\AuditService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\VenueService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService,
    );
});

it('returns venues for a valid user', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    // Create venues in this department
    $venues = Venue::factory()->count(3)->create(['department_id' => $department->id]);

    // Mock userService to return this user
    $this->userService
        ->shouldReceive('findUserById')
        ->with($user->id)
        ->andReturn($user);

    $result = $this->venueService->getVenuesWithDirectorId($user->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($result->every(fn($venue) => $venue->department_id == $department->id))->toBeTrue();

});

it('throws an InvalidArgumentException if user_id is negative', function () {
    $this->venueService->getVenuesWithDirectorId(-1);
})->throws(\InvalidArgumentException::class, 'Venue id must be greater than zero.');
