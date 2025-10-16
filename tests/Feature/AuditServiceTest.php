<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve the service from Laravel's service container.
        $this->auditService = $this->app->make(AuditService::class);
    }

    // ------------------------------------------------------------------
    // Tests for Writing Logs
    // ------------------------------------------------------------------

    #[Test]
    public function it_successfully_logs_a_standard_user_action(): void
    {
        $user = User::factory()->create();
        $this->auditService->logAction($user->user_id, 'EVENT_CREATED', 'User created an event.');

        $this->assertDatabaseHas('audit_trail', [
            'user_id' => $user->user_id,
            'at_action' => 'EVENT_CREATED',
            'is_admin_action' => false,
        ]);
    }

    #[Test]
    public function it_successfully_logs_a_high_privilege_admin_action(): void
    {
        $admin = User::factory()->create();
        $this->auditService->logAdminAction($admin->user_id, 'USER_DELETED', 'Admin deleted a user.');

        $this->assertDatabaseHas('audit_trail', [
            'user_id' => $admin->user_id,
            'at_action' => 'USER_DELETED',
            'is_admin_action' => true,
        ]);
    }

    #[Test]
    public function log_methods_return_a_valid_audittrail_model_instance(): void
    {
        $user = User::factory()->create();
        $result = $this->auditService->logAction($user->user_id, 'TEST_ACTION', 'A test action.');

        $this->assertInstanceOf(AuditTrail::class, $result);
        $this->assertEquals('TEST_ACTION', $result->at_action);
    }

    // ------------------------------------------------------------------
    // Tests for Reading & Filtering Logs
    // ------------------------------------------------------------------

    #[Test]
    public function it_retrieves_all_records_with_no_filters(): void
    {
        AuditTrail::factory()->count(5)->create();

        $results = $this->auditService->getAuditTrail();

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertEquals(5, $results->total());
    }

    #[Test]
    public function it_correctly_filters_by_a_specific_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        AuditTrail::factory()->create(['user_id' => $userA->user_id]);
        AuditTrail::factory()->count(2)->create(['user_id' => $userB->user_id]);

        $results = $this->auditService->getAuditTrail(['user_id' => $userA->user_id]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($userA->user_id, $results->first()->user_id);
    }

    #[Test]
    public function it_correctly_filters_by_a_specific_action_code(): void
    {
        AuditTrail::factory()->create(['at_action' => 'EVENT_CREATED']);
        AuditTrail::factory()->count(2)->create(['at_action' => 'USER_UPDATED']);

        $results = $this->auditService->getAuditTrail(['at_action' => 'EVENT_CREATED']);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('EVENT_CREATED', $results->first()->at_action);
    }

    #[Test]
    public function it_correctly_filters_for_admin_actions(): void
    {
        AuditTrail::factory()->create(['is_admin_action' => true]);
        AuditTrail::factory()->count(3)->create(['is_admin_action' => false]);

        $results = $this->auditService->getAuditTrail(['is_admin_action' => true]);

        $this->assertEquals(1, $results->total());
        $this->assertTrue($results->first()->is_admin_action);
    }

    #[Test]
    public function it_correctly_handles_a_combination_of_filters(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        // The one record we expect to find
        AuditTrail::factory()->create(['user_id' => $userA->user_id, 'is_admin_action' => true]);
        // Other records that should be filtered out
        AuditTrail::factory()->create(['user_id' => $userA->user_id, 'is_admin_action' => false]);
        AuditTrail::factory()->create(['user_id' => $userB->user_id, 'is_admin_action' => true]);

        $results = $this->auditService->getAuditTrail([
            'user_id' => $userA->user_id,
            'is_admin_action' => true,
        ]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($userA->user_id, $results->first()->user_id);
        $this->assertTrue($results->first()->is_admin_action);
    }
}

