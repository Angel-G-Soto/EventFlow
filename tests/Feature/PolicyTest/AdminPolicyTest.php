<?php

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Policies\AdminPolicy;

beforeEach(function () {
    $this->policy = new AdminPolicy();

    // Departments (for parity with your VenuePolicy setup; not strictly needed here)
    $this->department1 = Department::factory()->create();
    $this->department2 = Department::factory()->create();

    // Roles
    $this->systemAdminRole       = Role::factory()->create(['name' => 'system-admin']);
    $this->departmentDirectorRole = Role::factory()->create(['name' => 'department-director']);
    $this->departmentManagerRole  = Role::factory()->create(['name' => 'venue-manager']);

    // Users
    $this->systemAdmin = User::factory()->create();
    $this->systemAdmin->roles()->attach($this->systemAdminRole);

    $this->departmentDirector = User::factory()->create(['department_id' => $this->department1->id]);
    $this->departmentDirector->roles()->attach($this->departmentDirectorRole);

    $this->departmentManager = User::factory()->create(['department_id' => $this->department1->id]);
    $this->departmentManager->roles()->attach($this->departmentManagerRole);

    $this->multiRoleNonAdmin = User::factory()->create(['department_id' => $this->department1->id]);
    $this->multiRoleNonAdmin->roles()->attach([
        $this->departmentDirectorRole->id,
        $this->departmentManagerRole->id,
    ]);

    $this->regularUser = User::factory()->create();
});

it('allows system-admin to access the admin dashboard', function () {
    expect($this->policy->accessDashboard($this->systemAdmin))->toBeTrue();
});

it('denies non-admin users from accessing the admin dashboard', function () {
    expect($this->policy->accessDashboard($this->departmentDirector))->toBeFalse();
    expect($this->policy->accessDashboard($this->departmentManager))->toBeFalse();
    expect($this->policy->accessDashboard($this->multiRoleNonAdmin))->toBeFalse();
    expect($this->policy->accessDashboard($this->regularUser))->toBeFalse();
});

it('allows system-admin to perform overrides', function () {
    expect($this->policy->performOverride($this->systemAdmin))->toBeTrue();
});

it('denies non-admin users from performing overrides', function () {
    expect($this->policy->performOverride($this->departmentDirector))->toBeFalse();
    expect($this->policy->performOverride($this->departmentManager))->toBeFalse();
    expect($this->policy->performOverride($this->multiRoleNonAdmin))->toBeFalse();
    expect($this->policy->performOverride($this->regularUser))->toBeFalse();
});

it('allows system-admin to manage users', function () {
    expect($this->policy->manageUsers($this->systemAdmin))->toBeTrue();
});

it('denies non-admin users from managing users', function () {
    expect($this->policy->manageUsers($this->departmentDirector))->toBeFalse();
    expect($this->policy->manageUsers($this->departmentManager))->toBeFalse();
    expect($this->policy->manageUsers($this->multiRoleNonAdmin))->toBeFalse();
    expect($this->policy->manageUsers($this->regularUser))->toBeFalse();
});
