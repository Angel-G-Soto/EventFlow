<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditService;
use App\Services\DepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentService $departmentService;
    private MockInterface $auditServiceMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of the AuditService to isolate our tests.
        $this->auditServiceMock = Mockery::mock(AuditService::class);

        // Manually create an instance of DepartmentService, injecting our mock.
        $this->departmentService = new DepartmentService($this->auditServiceMock);
    }

    #[Test]
    public function it_creates_a_department_and_logs_action(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $data = 'College of Engineering';

        $this->auditServiceMock->shouldReceive('logAdminAction')->once();

        // Act
        $this->departmentService->createDepartment($data, $admin);

        // Assert
        $this->assertDatabaseHas('Department', ['d_code' => 'college-of-engineering']);
    }

    #[Test]
    public function it_updates_a_department_and_logs_as_admin(): void
    {
        // Arrange
        $adminRole = Role::factory()->create(['r_name' => 'System Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('system-admin');
        $department = Department::factory()->create(['d_name' => 'Old Name']);
        $data = 'New Name';

        $this->auditServiceMock->shouldReceive('logAdminAction')->once();
        $this->auditServiceMock->shouldNotReceive('logAction'); // Ensure the standard log is NOT called

        // Act
        $this->departmentService->updateDepartment($department, $data, $admin);

        // Assert
        $this->assertDatabaseHas('Department', ['department_id' => $department->department_id, 'd_name' => 'New Name']);
    }


    #[Test]
    public function it_deletes_a_department_unassigns_children_and_logs_action(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $department = Department::factory()->create();
        $userInDept = User::factory()->create(['department_id' => $department->department_id]);
        $venueInDept = Venue::factory()->create(['department_id' => $department->deparment_id]);

        $this->auditServiceMock->shouldReceive('logAdminAction')->once();

        // Act
        $this->departmentService->deleteDepartment($department, $admin);

        // Assert
        $this->assertDatabaseMissing('Department', ['department_id' => $department->department_id]);
        $this->assertDatabaseHas('User', ['user_id' => $userInDept->user_id, 'department_id' => null]);
        $this->assertDatabaseHas('Venue', ['venue_id' => $venueInDept->venue_id, 'department_id' => null]);
    }

    #[Test]
    public function it_retrieves_all_users_for_a_department(): void
    {
        // Arrange
        $department = Department::factory()->create();
        $user1 = User::factory()->create(['department_id' => $department->department_id]);
        $user2 = User::factory()->create(['department_id' => $department->department_id]);
        $userFromOtherDept = User::factory()->create(); // Belongs to a different department

        // Act
        $departmentUsers = $this->departmentService->getDepartmentUsers($department);

        // Assert
        $this->assertCount(2, $departmentUsers);
        $this->assertTrue($departmentUsers->contains($user1));
        $this->assertTrue($departmentUsers->contains($user2));
        $this->assertFalse($departmentUsers->contains($userFromOtherDept));
    }

    #[Test]
    public function it_retrieves_all_venues_for_a_department(): void
    {
        // Arrange
        $department = Department::factory()->create();
        $venue1 = Venue::factory()->create(['department_id' => $department->department_id]);
        $venue2 = Venue::factory()->create(['department_id' => $department->department_id]);
        $venueFromOtherDept = Venue::factory()->create(); // Belongs to a different department

        // Act
        $departmentVenues = $this->departmentService->getDepartmentVenues($department);

        // Assert
        $this->assertCount(2, $departmentVenues);
        $this->assertTrue($departmentVenues->contains($venue1));
        $this->assertTrue($departmentVenues->contains($venue2));
        $this->assertFalse($departmentVenues->contains($venueFromOtherDept));
    }
}
