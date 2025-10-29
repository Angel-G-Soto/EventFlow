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

it('allows director to access director venue index', function () {
    // Create a department
    $department = Department::factory()->create();

    // Create a user and assign to department
    $director = User::factory()->create(['department_id' => $department->id]);

    // Create a role and attach to user
    $role = Role::factory()->create(['name' => 'department-director']);
    $director->roles()->attach($role->id);

    // Mock Auth
    Auth::shouldReceive('user')->zeroOrMoreTimes()->andReturn($director);

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
        ->with(['department_id' => $department->id])
        ->zeroOrMoreTimes()
        ->andReturn($venues);

    $this->app->instance(VenueService::class, $venueServiceMock);

    // Call controller
    $controller = $this->app->make(VenueController::class);
    $response = $controller->directorVenueIndex();

    // Assert view and data
    expect($response->name())->toBe('venue.directorVenueIndex')
        ->and($response->getData()['venues'])->toBe($venues);
});

it('denies access for non-directors', function () {
    $department = Department::factory()->create();

    // Create a user without the director role
    $user = User::factory()->create(['department_id' => $department->id]);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $controller = $this->app->make(VenueController::class);

    $this->expectException(HttpException::class);
    $controller->directorVenueIndex();
});
