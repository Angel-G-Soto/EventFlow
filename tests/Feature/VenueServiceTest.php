<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Event;
use App\Models\OpeningHour;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditService;
use App\Services\EventService;
use App\Services\VenueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VenueServiceTest extends TestCase
{
    use RefreshDatabase;

    private VenueService $venueService;
    private MockInterface $auditServiceMock;
    // private MockInterface $eventServiceMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for the service's dependencies.
        $this->auditServiceMock = Mockery::mock(AuditService::class);
        // $this->eventServiceMock = Mockery::mock(EventService::class);

        // Manually create an instance of VenueService, injecting our mocks.
        $this->venueService = new VenueService($this->auditServiceMock);
    }

    // #[Test]
    // public function get_available_venues_filters_out_booked_and_closed_venues(): void
    // {
    //     // Arrange
    //     $startTime = Carbon::parse('2025-10-20 10:00:00');
    //     $endTime = Carbon::parse('2025-10-20 12:00:00');

    //     // Venue 1: Available and Open
    //     $availableVenue = Venue::factory()->create(['v_is_active' => true]);
    //     OpeningHour::factory()->create([
    //         'venue_id' => $availableVenue->venue_id,
    //         'day_of_week' => 1, // Monday
    //         'open_time' => '08:00',
    //         'close_time' => '17:00',
    //     ]);

    //     // Venue 2: Booked
    //     $bookedVenue = Venue::factory()->create(['v_is_active' => true]);

    //     // Venue 3: Closed at the requested time
    //     $closedVenue = Venue::factory()->create(['v_is_active' => true]);
    //     OpeningHour::factory()->create([
    //         'venue_id' => $closedVenue->venue_id,
    //         'day_of_week' => 1, // Monday
    //         'open_time' => '13:00', // Opens after the requested time
    //         'close_time' => '20:00',
    //     ]);

    //     // Venue 4: Inactive
    //     Venue::factory()->create(['v_is_active' => false]);

    //     // Mock the EventService to return the ID of the booked venue.
    //     $this->eventServiceMock
    //         ->shouldReceive('getBookedVenueIdsAtTime')
    //         ->once()
    //         ->with($startTime, $endTime)
    //         ->andReturn([$bookedVenue->venue_id]);

    //     // Act
    //     $availableVenues = $this->venueService->getAvailableVenues($startTime, $endTime);

    //     // Assert
    //     $this->assertCount(1, $availableVenues);
    //     $this->assertTrue($availableVenues->contains($availableVenue));
    //     $this->assertFalse($availableVenues->contains($bookedVenue));
    //     $this->assertFalse($availableVenues->contains($closedVenue));
    // }

    #[Test]
    public function update_or_create_from_import_data_creates_new_venue(): void
    {
        // Arrange
        $department = Department::factory()->create(['d_name' => 'Engineering']);
        $venueData = [
            'v_name' => 'New Lab',
            'v_code' => 'ENG-101',
            'department_name_raw' => 'Engineering',
            'v_features' => 'Computers',
            'v_capacity' => 30,
            'v_test_capacity' => 25,
        ];

        // Act
        $this->venueService->updateOrCreateFromImportData($venueData);

        // Assert
        $this->assertDatabaseHas('Venue', ['v_code' => 'ENG-101', 'v_name' => 'New Lab']);
    }

    #[Test]
    public function update_or_create_from_import_data_updates_existing_venue(): void
    {
        // Arrange
        $department = Department::factory()->create(['d_name' => 'Engineering']);
        Venue::factory()->create(['v_code' => 'ENG-101', 'v_name' => 'Old Name']);
        $venueData = ['v_name' => 'New Name', 'v_code' => 'ENG-101', 'department_name_raw' => 'Engineering', 'v_features' => 'Computers', 'v_capacity' => 30, 'v_test_capacity' => 25];

        // Act
        $this->venueService->updateOrCreateFromImportData($venueData);

        // Assert
        $this->assertDatabaseHas('Venue', ['v_code' => 'ENG-101', 'v_name' => 'New Name']);
        $this->assertDatabaseMissing('Venue', ['v_code' => 'ENG-101', 'v_name' => 'Old Name']);
        $this->assertDatabaseCount('Venue', 1);
    }


    #[Test]
    public function update_venue_changes_details_and_audits(): void
    {
        // Arrange
        $editor = User::factory()->create();
        $venue = Venue::factory()->create(['v_capacity' => 100]);
        $newData = ['v_capacity' => 150];

        $this->auditServiceMock->shouldReceive('logAction')->once();

        // Act
        $this->venueService->updateVenue($venue, $newData, $editor);

        // Assert
        $this->assertDatabaseHas('Venue', ['venue_id' => $venue->venue_id, 'v_capacity' => 150]);
    }

    #[Test]
    public function update_opening_hours_replaces_old_hours_and_audits(): void
    {
        // Arrange
        $editor = User::factory()->create();
        $venue = Venue::factory()->create();
        OpeningHour::factory()->create(['venue_id' => $venue->venue_id, 'day_of_week' => 1]); // Old hours
        $newHoursData = [
            ['day_of_week' => 2, 'open_time' => '09:00', 'close_time' => '18:00'], // New hours for Tuesday
        ];

        $this->auditServiceMock->shouldReceive('logAction')->once();

        // Act
        $this->venueService->updateOpeningHours($venue, $newHoursData, $editor);

        // Assert
        $this->assertDatabaseCount('opening_hour', 1);
        $this->assertDatabaseHas('opening_hour', ['venue_id' => $venue->venue_id, 'day_of_week' => 2]);
        $this->assertDatabaseMissing('opening_hour', ['venue_id' => $venue->venue_id, 'day_of_week' => 1]);
    }

    #[Test]
    public function get_venue_by_id_returns_correct_venue(): void
    {
        // Arrange
        $venue = Venue::factory()->create();

        // Act
        $foundVenue = $this->venueService->getVenueById($venue->venue_id);

        // Assert
        $this->assertInstanceOf(Venue::class, $foundVenue);
        $this->assertEquals($venue->venue_id, $foundVenue->venue_id);
    }

    #[Test]
    public function get_venues_for_manager_returns_only_their_venues(): void
    {
        // Arrange
        $managerA = User::factory()->create();
        $managerB = User::factory()->create();
        $venueA1 = Venue::factory()->create(['manager_id' => $managerA->user_id]);
        $venueA2 = Venue::factory()->create(['manager_id' => $managerA->user_id]);
        $venueB1 = Venue::factory()->create(['manager_id' => $managerB->user_id]);

        // Act
        $managerAVenues = $this->venueService->getVenuesForManager($managerA);

        // Assert
        $this->assertCount(2, $managerAVenues);
        $this->assertTrue($managerAVenues->contains($venueA1));
        $this->assertTrue($managerAVenues->contains($venueA2));
        $this->assertFalse($managerAVenues->contains($venueB1));
    }

    #[Test]
    public function get_venues_for_department_returns_only_their_venues(): void
    {
        // Arrange
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $venueA1 = Venue::factory()->create(['department_id' => $deptA->department_id]);
        $venueA2 = Venue::factory()->create(['department_id' => $deptA->department_id]);
        $venueB1 = Venue::factory()->create(['department_id' => $deptB->department_id]);

        // Act
        $deptAVenues = $this->venueService->getVenuesForDepartment($deptA);

        // Assert
        $this->assertCount(2, $deptAVenues);
        $this->assertTrue($deptAVenues->contains($venueA1));
        $this->assertTrue($deptAVenues->contains($venueA2));
        $this->assertFalse($deptAVenues->contains($venueB1));
    }
}
