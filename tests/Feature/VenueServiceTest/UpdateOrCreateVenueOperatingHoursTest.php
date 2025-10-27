<?php


use App\Models\AuditTrail;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;
use App\Services\AuditService;
use Carbon\Carbon;

beforeEach(function () {
    $this->auditService = Mockery::mock(AuditService::class);

    $this->venueService = new VenueService(
        departmentService: Mockery::mock(\App\Services\DepartmentService::class),
        useRequirementService: Mockery::mock(\App\Services\UseRequirementService::class),
        auditService: $this->auditService
    );

    $this->venue = Venue::factory()->create();

    $this->manager = Mockery::mock(User::class)->makePartial();
    $this->manager->id = 1;
    $this->manager->name = 'Manager';
});

it('updates operating hours successfully', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $opening = Carbon::parse('09:00');
    $closing = Carbon::parse('18:00');

    $this->auditService
        ->shouldReceive('logAction')
        ->once()
        ->with($this->manager->id, '', 'Updated operating hours for venue #' . $this->venue->id)
        ->andReturn(Mockery::mock(AuditTrail::class));

    $updatedVenue = $this->venueService->updateVenueOperatingHours(
        $this->venue,
        $opening,
        $closing,
        $this->manager
    );

    expect($updatedVenue->opening_time->format('H:i'))
        ->toBe($opening->format('H:i'))
        ->and($updatedVenue->closing_time->format('H:i'))
        ->toBe($closing->format('H:i'));
});

it('throws exception if user is not venue-manager', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['random-role']));

    $opening = Carbon::parse('09:00');
    $closing = Carbon::parse('18:00');

    $this->auditService->shouldNotReceive('logAction');

    $this->venueService->updateVenueOperatingHours(
        $this->venue,
        $opening,
        $closing,
        $this->manager
    );
})->throws(InvalidArgumentException::class, 'The user must be venue-manager.');

it('throws generic exception if audit logging fails', function () {
    $this->manager->shouldReceive('getRoleNames')->andReturn(collect(['venue-manager']));

    $opening = Carbon::parse('09:00');
    $closing = Carbon::parse('18:00');

    $this->auditService
        ->shouldReceive('logAction')
        ->once()
        ->andThrow(new RuntimeException('Audit failed'));

    $this->venueService->updateVenueOperatingHours(
        $this->venue,
        $opening,
        $closing,
        $this->manager
    );
})->throws(Exception::class, 'Unable to update or create the operating hours.');
