<?php

use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use App\Models\Role;
use App\Policies\VenuePolicy;

beforeEach(function () {
    $this->policy = new VenuePolicy();

    $this->department1 = Department::factory()->create();
    $this->department2 = Department::factory()->create();

    $this->venue = Venue::factory()->create([
        'department_id' => $this->department1->id,
    ]);

    // Create roles
    $this->systemAdminRole = Role::factory()->create(['name' => 'system-admin']);
    $this->departmentDirectorRole = Role::factory()->create(['name' => 'department-director']);
    $this->departmentManagerRole = Role::factory()->create(['name' => 'department-manager']);

    // Create users
    $this->systemAdmin = User::factory()->create();
    $this->systemAdmin->roles()->attach($this->systemAdminRole);

    $this->departmentDirector = User::factory()->create(['department_id' => $this->department1->id]);
    $this->departmentDirector->roles()->attach($this->departmentDirectorRole);

    $this->otherDirector = User::factory()->create(['department_id' => $this->department2->id]);
    $this->otherDirector->roles()->attach($this->departmentDirectorRole);

    $this->departmentManager = User::factory()->create(['department_id' => $this->department1->id]);
    $this->departmentManager->roles()->attach($this->departmentManagerRole);

    $this->otherManager = User::factory()->create(['department_id' => $this->department2->id]);
    $this->otherManager->roles()->attach($this->departmentManagerRole);

    $this->regularUser = User::factory()->create();
});

it('allows system-admin to view any venues', function () {
    expect($this->policy->viewAny($this->systemAdmin))->toBeTrue();
});

it('denies non-admin to view any venues', function () {
    expect($this->policy->viewAny($this->regularUser))->toBeFalse();
});

it('allows proper roles to view a venue in their department', function () {
    expect($this->policy->view($this->systemAdmin, $this->venue))->toBeTrue();
    expect($this->policy->view($this->departmentDirector, $this->venue))->toBeTrue();
    expect($this->policy->view($this->departmentManager, $this->venue))->toBeTrue();
});

it('denies access to users from other departments', function () {
    expect($this->policy->view($this->otherDirector, $this->venue))->toBeFalse();
    expect($this->policy->view($this->otherManager, $this->venue))->toBeFalse();
    expect($this->policy->view($this->regularUser, $this->venue))->toBeFalse();
});

it('allows only system-admin to create a venue', function () {
    expect($this->policy->create($this->systemAdmin))->toBeTrue();
    expect($this->policy->create($this->departmentDirector))->toBeFalse();
    expect($this->policy->create($this->departmentManager))->toBeFalse();
    expect($this->policy->create($this->regularUser))->toBeFalse();
});

it('allows system-admin and correct department users to update a venue', function () {
    expect($this->policy->update($this->systemAdmin, $this->venue))->toBeTrue();
    expect($this->policy->update($this->departmentDirector, $this->venue))->toBeTrue();
    expect($this->policy->update($this->departmentManager, $this->venue))->toBeTrue();
});

it('denies update for other departments and regular users', function () {
    expect($this->policy->update($this->otherDirector, $this->venue))->toBeFalse();
    expect($this->policy->update($this->otherManager, $this->venue))->toBeFalse();
    expect($this->policy->update($this->regularUser, $this->venue))->toBeFalse();
});

it('allows only system-admin to delete a venue', function () {
    expect($this->policy->delete($this->systemAdmin))->toBeTrue();
    expect($this->policy->delete($this->departmentDirector))->toBeFalse();
    expect($this->policy->delete($this->departmentManager))->toBeFalse();
    expect($this->policy->delete($this->regularUser))->toBeFalse();
});

it('allows system-admin and department director to assign manager', function () {
    expect($this->policy->assignManager($this->systemAdmin, $this->venue))->toBeTrue();
    expect($this->policy->assignManager($this->departmentDirector, $this->venue))->toBeTrue();
});

it('denies assigning manager for department managers and other directors', function () {
    expect($this->policy->assignManager($this->departmentManager, $this->venue))->toBeFalse();
    expect($this->policy->assignManager($this->otherDirector, $this->venue))->toBeFalse();
    expect($this->policy->assignManager($this->regularUser, $this->venue))->toBeFalse();
});

it('allows system-admin and correct department users to update requirements', function () {
    expect($this->policy->updateRequirements($this->systemAdmin, $this->venue))->toBeTrue();
    expect($this->policy->updateRequirements($this->departmentDirector, $this->venue))->toBeTrue();
    expect($this->policy->updateRequirements($this->departmentManager, $this->venue))->toBeTrue();
});

it('denies update requirements for users from other departments or regular users', function () {
    expect($this->policy->updateRequirements($this->otherDirector, $this->venue))->toBeFalse();
    expect($this->policy->updateRequirements($this->otherManager, $this->venue))->toBeFalse();
    expect($this->policy->updateRequirements($this->regularUser, $this->venue))->toBeFalse();
});
