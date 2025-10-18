<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;
    private MockInterface $auditServiceMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of the AuditService. This allows us to test that
        // our UserService calls the audit methods without actually running them.
        $this->auditServiceMock = Mockery::mock(AuditService::class);

        // Manually create an instance of UserService, injecting our mock.
        $this->userService = new UserService($this->auditServiceMock);
    }

    #[Test]
    public function find_or_create_user_returns_existing_user(): void
    {
        // Arrange: Create a user that already exists in the database.
        $existingUser = User::factory()->create([
            'u_email' => 'jane.doe@example.com',
            'u_name' => 'Jane Doe'
        ]);

        // Act: Call the method with the same email.
        $foundUser = $this->userService->findOrCreateUser('jane.doe@example.com', 'Jane Doe');

        // Assert: Ensure it returned the correct user and didn't create a new one.
        $this->assertEquals($existingUser->user_id, $foundUser->user_id);
        $this->assertDatabaseCount('User', 1);
    }

    #[Test]
    public function find_or_create_user_creates_new_user_if_not_exists(): void
    {
        // Act: Call the method with an email that doesn't exist.
        $newUser = $this->userService->findOrCreateUser('john.doe@example.com', 'John Doe');

        // Assert: Ensure a new user was created with the correct details.
        $this->assertNotNull($newUser);
        $this->assertEquals('john.doe@example.com', $newUser->u_email);
        $this->assertDatabaseHas('User', ['u_email' => 'john.doe@example.com']);
        $this->assertDatabaseCount('User', 1);
    }

    #[Test]
    public function update_user_roles_correctly_syncs_roles_and_audits(): void
    {
        // Arrange: Create an admin, a user, and some roles.
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['r_code' => 'space-manager']);
        $role2 = Role::factory()->create(['r_code' => 'dsca-staff']);

        // Expect the AuditService's logAdminAction method to be called once.
        $this->auditServiceMock
            ->shouldReceive('logAdminAction')
            ->once()
            ->with(
                $admin->user_id,
                $admin->u_name,
                'USER_ROLES_UPDATED',
                Mockery::any() // We don't need to assert the exact description string
            );

        // Act: Call the method to assign the roles.
        $this->userService->updateUserRoles($user, ['space-manager', 'dsca-staff'], $admin);

        // Assert: Ensure the user now has exactly these two roles.
        $this->assertCount(2, $user->fresh()->roles);
        $this->assertTrue($user->fresh()->roles->contains($role1));
        $this->assertTrue($user->fresh()->roles->contains($role2));
    }

    #[Test]
    public function assign_user_to_department_updates_user_and_audits(): void
    {
        // Arrange: Create an admin, a user, and a department.
        $admin = User::factory()->create();
        $user = User::factory()->create(['department_id' => null]);
        $department = Department::factory()->create();

        // Expect the AuditService's logAdminAction method to be called once.
        $this->auditServiceMock
            ->shouldReceive('logAdminAction')
            ->once()
            ->with(
                $admin->user_id,
                $admin->u_name,
                'USER_DEPT_ASSIGNED',
                "Assigned user '{$user->u_name}' to department '{$department->d_name}'."
            );

        // Act: Assign the user to the department.
        $this->userService->assignUserToDepartment($user, $department->department_id, $admin);

        // Assert: Ensure the user's department_id has been updated.
        $this->assertEquals($department->department_id, $user->fresh()->department_id);
    }

    #[Test]
    public function get_users_with_role_returns_correct_users(): void
    {
        // Arrange: Create a role and several users.
        $dscaRole = Role::factory()->create(['r_code' => 'dsca-staff']);
        $userWithRole1 = User::factory()->create();
        $userWithRole2 = User::factory()->create();
        $userWithoutRole = User::factory()->create();

        // Assign the role to two of the users.
        $userWithRole1->roles()->attach($dscaRole);
        $userWithRole2->roles()->attach($dscaRole);

        // Act: Call the method to get users with the 'dsca-staff' role.
        $dscaUsers = $this->userService->getUsersWithRole('dsca-staff');

        // Assert: Ensure the collection contains only the correct users.
        $this->assertCount(2, $dscaUsers);
        $this->assertTrue($dscaUsers->contains($userWithRole1));
        $this->assertTrue($dscaUsers->contains($userWithRole2));
        $this->assertFalse($dscaUsers->contains($userWithoutRole));
    }

     #[Test]
    public function update_user_profile_changes_data_and_audits(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'u_name' => 'Old Name',
            'u_email' => 'Old Email',
        ]);
        $newData = [
            'u_name' => 'New Name',
            'u_email' => 'New Email',
        ];

        $this->auditServiceMock
            ->shouldReceive('logAdminAction')
            ->once()
            ->with(
                $admin->user_id,
                $admin->u_name,
                'USER_PROFILE_UPDATED',
                Mockery::any()
            );

        // Act
        $this->userService->updateUserProfile($user, $newData, $admin);

        // Assert
        $this->assertDatabaseHas('User', [
            'user_id' => $user->user_id,
            'u_name' => 'New Name'
        ]);
        $this->assertDatabaseMissing('User', [
            'user_id' => $user->user_id,
            'u_name' => 'Old Name',
        ]);

        $this->assertDatabaseHas('User', [
            'user_id' => $user->user_id,
            'u_email' => 'New Email'
        ]);
        $this->assertDatabaseMissing('User', [
            'user_id' => $user->user_id,
            'u_email' => 'Old Email',
        ]);
    }

    #[Test]
    public function delete_user_removes_record_and_audits(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $userToDelete = User::factory()->create();

        $this->auditServiceMock
            ->shouldReceive('logAdminAction')
            ->once()
            ->with(
                $admin->user_id,
                $admin->u_name,
                'USER_DELETED',
                "Permanently deleted user '{$userToDelete->u_name}' (Email: {$userToDelete->u_email}) (ID: {$userToDelete->user_id})."
            );

        // Act
        $this->userService->deleteUser($userToDelete, $admin);

        // Assert
        $this->assertDatabaseMissing('User', [
            'user_id' => $userToDelete->user_id,
        ]);
    }
}
