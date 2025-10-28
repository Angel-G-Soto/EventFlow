
<?php

use App\Models\Department;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fake the blade renders for testing:
    //View::shouldReceive('make')->byDefault()
    //    ->andReturn(Mockery::mock(ViewContract::class));
    //View::shouldReceive('make')->andReturn(response('ok',200));
    //View::shouldReceive('exists')->andReturnTrue();

    // Roles
    $this->systemAdminRole = Role::factory()->create(['name' => 'system-admin']);
    $this->nonAdminRole    = Role::factory()->create(['name' => 'department-director']);

    // Users
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($this->systemAdminRole);

    $this->nonAdmin = User::factory()->create();
    $this->nonAdmin->roles()->attach($this->nonAdminRole);

    // Common data (only if needed for views)
    $this->department = Department::factory()->create();
    $this->venue      = Venue::factory()->create(['department_id' => $this->department->id]);
    $this->event      = Event::factory()->create([
        'venue_id'   => $this->venue->id,
        'creator_id' => $this->admin->id,
        'status'     => 'pending approval - manager',
    ]);
});

/**
 * 403s for non-admin on every admin route
 */
it('blocks non-admin from users page', function () {
    $this->actingAs($this->nonAdmin);
    $this->get(route('admin.users.index'))->assertStatus(403);
});

it('blocks non-admin from departments page', function () {
    $this->actingAs($this->nonAdmin);
    $this->get(route('admin.departments.index'))->assertStatus(403);
});

it('blocks non-admin from venues page', function () {
    $this->actingAs($this->nonAdmin);
    $this->get(route('admin.venues.index'))->assertStatus(403);
});

it('blocks non-admin from events page', function () {
    $this->actingAs($this->nonAdmin);
    $this->get(route('admin.events.index'))->assertStatus(403);
});

it('blocks non-admin from overrides index', function () {
    $this->actingAs($this->nonAdmin);
    $this->get(route('admin.overrides.index'))->assertStatus(403);
});

/**
 * Admin happy paths: 200 OK (or redirect) on all routes
 * Note: If the Blade views aren't integrated, remove assertViewIs(...)
 * or create minimal stubs at resources/views/admin/.../index.blade.php
 */
it('allows admin to view users page', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.users.index'))
        ->assertStatus(200);
    // ->assertViewIs('admin.users.index');
});

it('allows admin to view departments page', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.departments.index'))
        ->assertStatus(200);
    // ->assertViewIs('admin.departments.index');
});

it('allows admin to view venues page', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.venues.index'))
        ->assertStatus(200);
    // ->assertViewIs('admin.venues.index');
});

it('allows admin to view events page', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.events.index'))
        ->assertStatus(200);
    // ->assertViewIs('admin.events.index');
});

it('allows admin to view overrides index', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.overrides.index'))
        ->assertStatus(200);
    // ->assertViewIs('admin.overrides.index');
});
