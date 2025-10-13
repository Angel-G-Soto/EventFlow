<?php

namespace Tests\Feature;

use App\Services\AuditService;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The instance of our service.
     * @var AuditService
     */
    private $auditService;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Resolve the service from Laravel's service container
        $this->auditService = $this->app->make(AuditService::class);
    }


    #[Test]
    public function it_successfully_logs_a_standard_user_action(): void
    {
       // Arrange: Create a user first using the factory
        $user = User::factory()->create();

        // Define the data for the audit log using the created user's ID
        $actionCode = 'EVENT_CREATED';
        $description = "User {$user->u_name} created event 'Spring Fling'.";

        // Act: Call the logAction method
        $this->auditService->logAction($user->user_id, $user->u_name, $actionCode, $description);

        // Assert: Verify that the record exists in the database
        $this->assertDatabaseHas('audit_trail', [
            'user_id' => $user->user_id,
            'at_action' => $actionCode,
            'at_description' => $description,
            'at_user' => $user->u_name
        ]);
    }

    #[Test]
    public function it_successfully_logs_an_admin_action(): void
    {
       // Arrange: Create an admin user first
        $admin = User::factory()->create();

        $actionCode = 'ADMIN_OVERRIDE';
        $description = "Admin force-approved event #123.";

        // Act: Call the logAdminAction method
        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // Assert: Verify that the record exists in the database
        $this->assertDatabaseHas('audit_trail', [
            'user_id' => $admin->user_id,
            'at_action' => $actionCode,
            'at_description' => $description,
            'at_user' => $admin->u_name
        ]);
    }

    #[Test]
    public function log_action_returns_an_audittrail_model_instance(): void
    {
        // Arrange: Create a user first
        $user = User::factory()->create();

        $actionCode = 'EVENT_WITHDRAWN';
        $description = 'Event was withdrawn.';

        // Act: Call the method and capture the return value
        $auditTrailInstance = $this->auditService->logAction($user->user_id, $user->u_name, $actionCode, $description);

        // Assert: Check that the return value is an instance of the AuditTrail model
        $this->assertInstanceOf(AuditTrail::class, $auditTrailInstance);
        $this->assertEquals($user->user_id, $auditTrailInstance->user_id);
        $this->assertEquals($user->u_name, $auditTrailInstance->at_user);
    }
}