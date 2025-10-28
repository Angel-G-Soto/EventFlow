<?php

use App\Http\Controllers\VenueController;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Pagination\LengthAwarePaginator;

it('allows administrator to access administrator venue index', function () {
    // Create a department
    $department = Department::factory()->create();

    // Create a user and assign to department
    $administrator = User::factory()->create(['department_id' => $department->id]);

    // Create a role and attach to user
    $role = Role::factory()->create(['name' => 'system-administrator']);
    $administrator->roles()->attach($role->id);

    // Mock Auth
    Auth::shouldReceive('user')->zeroOrMoreTimes()->andReturn($administrator);

    // Create some venues in the same department
    $venuesCollection = Venue::factory()->count(2)->make(['department_id' => $department->id]);

    // Wrap in paginator
    $venues = new LengthAwarePaginator(
        $venuesCollection,
        $venuesCollection->count(), // total
        15 // per page
    );

    // Mock VenueService
    $venueServiceMock = Mockery::mock(VenueService::class);
    $venueServiceMock
        ->shouldReceive('getAllVenues')
        ->zeroOrMoreTimes()
        ->andReturn($venues);

    $this->app->instance(VenueService::class, $venueServiceMock);

    // Call controller
    $controller = $this->app->make(VenueController::class);
    $response = $controller->administratorVenueIndex();

    // Assert view and data
    expect($response->name())->toBe('venue.administratorVenueIndex')
        ->and($response->getData()['venues'])->toBe($venues);
});

it('denies access for non-administrators', function () {
    $department = Department::factory()->create();

    // Create a user without the administrator role
    $user = User::factory()->create(['department_id' => $department->id]);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $controller = $this->app->make(VenueController::class);

    $this->expectException(HttpException::class);
    $controller->administratorVenueIndex();
});
