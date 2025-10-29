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
    $this->departmentManagerRole = Role::factory()->create(['name' => 'venue-manager']);

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
    expect($this->policy->viewAny($this->departmentDirector))->toBeFalse()
        ->and($this->policy->viewAny($this->departmentManager))->toBeFalse()
        ->and($this->policy->viewAny($this->regularUser))->toBeFalse();
});

it('allows department-director or venue-manager to view venues in their department', function () {
    expect($this->policy->view($this->departmentDirector, $this->venue))->toBeTrue()
        ->and($this->policy->view($this->departmentManager, $this->venue))->toBeTrue();
});

it('denies users from other departments or regular users', function () {
    expect($this->policy->view($this->otherDirector, $this->venue))->toBeFalse()
        ->and($this->policy->view($this->otherManager, $this->venue))->toBeFalse()
        ->and($this->policy->view($this->regularUser, $this->venue))->toBeFalse();
});

it('allows only system-admin to create a venue', function () {
    expect($this->policy->create($this->systemAdmin))->toBeTrue()
        ->and($this->policy->create($this->departmentDirector))->toBeFalse()
        ->and($this->policy->create($this->departmentManager))->toBeFalse()
        ->and($this->policy->create($this->regularUser))->toBeFalse();
});

it('allows only system-admin to update a venue', function () {
    expect($this->policy->update($this->systemAdmin, $this->venue))->toBeTrue()
        ->and($this->policy->update($this->departmentDirector, $this->venue))->toBeFalse()
        ->and($this->policy->update($this->departmentManager, $this->venue))->toBeFalse()
        ->and($this->policy->update($this->regularUser, $this->venue))->toBeFalse();
});

it('allows only system-admin to delete a venue', function () {
    expect($this->policy->delete($this->systemAdmin))->toBeTrue()
        ->and($this->policy->delete($this->departmentDirector))->toBeFalse()
        ->and($this->policy->delete($this->departmentManager))->toBeFalse()
        ->and($this->policy->delete($this->regularUser))->toBeFalse();
});

it('allows department-director to assign manager in their department', function () {
    expect($this->policy->assignManager($this->departmentDirector, $this->venue))->toBeTrue();
});

it('denies assigning manager for other directors, managers, or regular users', function () {
    expect($this->policy->assignManager($this->otherDirector, $this->venue))->toBeFalse()
        ->and($this->policy->assignManager($this->departmentManager, $this->venue))->toBeFalse()
        ->and($this->policy->assignManager($this->regularUser, $this->venue))->toBeFalse();
});

it('allows venue-manager to update requirements and availability in their department', function () {
    expect($this->policy->updateRequirements($this->departmentManager, $this->venue))->toBeTrue()
        ->and($this->policy->updateAvailability($this->departmentManager, $this->venue))->toBeTrue();
});

it('denies updating requirements or availability for other departments, directors, or regular users', function () {
    expect($this->policy->updateRequirements($this->otherManager, $this->venue))->toBeFalse()
        ->and($this->policy->updateRequirements($this->departmentDirector, $this->venue))->toBeFalse()
        ->and($this->policy->updateRequirements($this->regularUser, $this->venue))->toBeFalse()
        ->and($this->policy->updateAvailability($this->otherManager, $this->venue))->toBeFalse()
        ->and($this->policy->updateAvailability($this->departmentDirector, $this->venue))->toBeFalse()
        ->and($this->policy->updateAvailability($this->regularUser, $this->venue))->toBeFalse();

});
