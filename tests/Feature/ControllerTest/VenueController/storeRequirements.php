<?php

use App\Http\Controllers\VenueController;
use App\Models\User;
use App\Models\Venue;
use App\Models\Department;
use App\Services\UserService;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('allows manager to store requirements', function () {
    // Create a department and a manager
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    // Create a venue in that department
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    // Mock Auth
    Auth::shouldReceive('user')->andReturn($manager);

    // Mock VenueService
    $venueServiceMock = Mockery::mock(VenueService::class);
    $venueServiceMock->shouldReceive('findById')
        ->with($venue->id)
        ->andReturn($venue)
        ->zeroOrMoreTimes();
    $venueServiceMock->shouldReceive('updateOrCreateVenueRequirements')
        ->with($venue, Mockery::type('array'), $manager)
        ->zeroOrMoreTimes();
    app()->instance(VenueService::class, $venueServiceMock);

    // Mock authorization policy
    Gate::shouldReceive('authorize')
        ->with('updateRequirements', [$manager, $venue])
        ->andReturnTrue();

    // Build a valid fake request
    $request = Request::create("/venues/{$venue->id}/requirements", 'POST', [
        'requirements' => [
            [
                'name' => 'Requirement 1',
                'hyperlink' => 'https://example.com/req1',
                'description' => 'First requirement',
            ],
            [
                'name' => 'Requirement 2',
                'hyperlink' => 'https://example.com/req2',
                'description' => 'Second requirement',
            ],
        ],
    ]);

    // Run controller
    $controller = app(VenueController::class);
    $response = $controller->storeRequirements($request, $venue->id);

    // Assert redirect
    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($response->getTargetUrl())->toBe(url('/'));
});

it('denies storing requirements when unauthorized', function () {
    // Create a department, user, and venue
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $venue = Venue::factory()->create(['department_id' => $department->id]);

    // Mock Auth
    Auth::shouldReceive('user')->andReturn($user);

    // Mock VenueService
    $venueServiceMock = Mockery::mock(VenueService::class);
    $venueServiceMock->shouldReceive('findById')
        ->with($venue->id)
        ->andReturn($venue);
    app()->instance(VenueService::class, $venueServiceMock);

    // Mock Gate to throw unauthorized
    Gate::shouldReceive('authorize')
        ->with('updateRequirements', [$user, $venue])
        ->andThrow(new HttpException(403, 'Forbidden'));

    // Build request
    $request = Request::create("/venues/{$venue->id}/requirements", 'POST', [
        'requirements' => [
            [
                'name' => 'Requirement 1',
                'hyperlink' => 'https://example.com/req1',
                'description' => 'First requirement',
            ],
        ],
    ]);

    // Run controller and assert exception
    $controller = app(VenueController::class);

    try {
        $controller->storeRequirements($request, $venue->id);
        $this->fail('Expected HttpException not thrown');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getMessage())->toContain('Forbidden');
    }
});
