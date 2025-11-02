<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Services\AuditService;
use App\Services\DepartmentService;
use App\Services\UseRequirementService;
use App\Services\UserService;
use App\Services\VenueService;

beforeEach(function () {
    $this->useRequirementService = Mockery::mock(UseRequirementService::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->departmentService = Mockery::mock(DepartmentService::class);
    $this->userService = Mockery::mock(UserService::class);

    $this->venueService = new VenueService(
        $this->departmentService,
        $this->useRequirementService,
        $this->auditService,
        $this->userService
    );

    $this->department = Department::factory()->create();
    $this->venue = Venue::factory()->create(['department_id' => $this->department->id]);
    $this->manager = User::factory()->create(['department_id' => $this->department->id]);

    // Give the manager the "venue-manager" role
    $role = Role::factory()->create(['name' => 'venue-manager']);
    $this->manager->roles()->attach($role->id);
});

it('throws an exception if manager does not have the venue-manager role', function () {
    $nonManager = User::factory()->create(['department_id' => $this->department->id]);

    $requirements = [[
        'name' => 'Food Certificate',
        'hyperlink' => 'https://example.com/foodcertificate',
        'description' => 'Ensure certification is granted if your are to distribute food in the venue',
    ]];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $nonManager);
})->throws(\InvalidArgumentException::class, 'Manager does not have the required role.');

it('throws an exception if manager does not belong to the venue department', function () {
    $otherDept = Department::factory()->create();
    $otherManager = User::factory()->create(['department_id' => $otherDept->id]);
    $role = Role::factory()->create(['name' => 'venue-manager']);
    $otherManager->roles()->attach($role->id);

    $requirements = [[
        'name' => 'Food Certificate',
        'hyperlink' => 'https://example.com/foodcertificate',
        'description' => 'Ensure certification is granted if your are to distribute food in the venue',
    ]];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $otherManager);
})->throws(\InvalidArgumentException::class, 'Manager does not belong to the venue department.');

it('throws an exception if a requirement is missing keys', function () {
    $requirements = [[
        'name' => 'Incomplete Requirement', // Missing hyperlink, description
    ]];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(\InvalidArgumentException::class, 'Missing keys in requirement at index 0: hyperlink, description');

it('throws an exception if any field is null', function () {
    $requirements = [[
        'name' => null,
        'hyperlink' => 'https://example.com',
        'description' => 'Details here',
    ]];

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);
})->throws(\InvalidArgumentException::class, "The field 'name' in requirement at index 0 cannot be null.");

it('successfully creates new venue requirements', function () {
    $requirements = [
        [
            'name' => 'Food Certificate',
            'hyperlink' => 'https://example.com/foodcertificate',
            'description' => 'Ensure certification is granted if your are to distribute food in the venue',
        ],
        [
            'name' => 'Health Certificate',
            'hyperlink' => 'https://example.com/healthcertificate',
            'description' => 'Comply with health certificate',
        ],
    ];

    $this->useRequirementService
        ->shouldReceive('deleteVenueUseRequirements')
        ->once()
        ->with($this->venue->id);

    $this->auditService
        ->shouldReceive('logAction')
        ->times(2); // Once per requirement
        //->with($this->manager->id, '', '', Mockery::on(fn($msg) => str_contains($msg, "Create requirement for venue #{$this->venue->id}")));

    $this->venueService->updateOrCreateVenueRequirements($this->venue, $requirements, $this->manager);

    $this->assertDatabaseHas('use_requirements', [
        'venue_id' => $this->venue->id,
        'name' => 'Food Certificate',
    ]);

    $this->assertDatabaseHas('use_requirements', [
        'venue_id' => $this->venue->id,
        'name' => 'Health Certificate',
    ]);
});
