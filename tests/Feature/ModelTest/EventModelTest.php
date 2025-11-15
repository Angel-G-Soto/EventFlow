<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Models\Category;
use App\Models\Document;
use App\Models\EventHistory;

it('belongs to a requester', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['creator_id' => $user->id]);

    expect($event->requester)->toBeInstanceOf(User::class)
        ->and($event->requester->id)->toBe($user->id);
});

it('belongs to a venue', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    expect($event->venue)->toBeInstanceOf(Venue::class)
        ->and($event->venue->id)->toBe($venue->id);
});

it('has many documents', function () {
    $event = Event::factory()->create();
    Document::factory()->count(3)->create(['event_id' => $event->id]);

    expect($event->documents)->toHaveCount(3)
        ->each->toBeInstanceOf(Document::class);
});

it('has many history records', function () {
    $event = Event::factory()->create();
    EventHistory::factory()->count(2)->create(['event_id' => $event->id]);

    expect($event->history)->toHaveCount(2)
        ->each->toBeInstanceOf(EventHistory::class);
});

it('belongs to many categories', function () {
    $event = Event::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $event->categories()->attach($categories->pluck('id'));

    expect($event->categories)->toHaveCount(2)
        ->each->toBeInstanceOf(Category::class);
});

it('allows mass assignment of fillable fields', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $data = [
        'creator_id' => $user->id,
        'venue_id' => $venue->id,
        'organization_name' => 'Order of the Phoenix',
        'organization_advisor_name' => 'Prof. Dumbledore',
        'organization_advisor_email' => 'albus.dumbledore@hogwarts.com',
        'organization_advisor_phone' => '787-832-4040',
        'title' => 'Phoenix Feathers',
        'description' => 'Discussion on phoenix feathers.',
        'status' => 'pending',
        'start_time' => now(),
        'end_time' => now()->addHours(2),
        'creator_institutional_number' => '123456', // renamed from student_number
        'creator_phone_number' => '787-832-4040',   // renamed from student_phone
        'guest_size' => 50,
        'handles_food' => false,
        'use_institutional_funds' => true,
        'external_guest' => false,
    ];

    $event = Event::create($data);

    foreach ($data as $key => $value) {
        expect($event->{$key})->toEqual($value);
    }
});

it('returns all history records for the event', function () {
    $event = Event::factory()->create();

    $history1 = EventHistory::factory()->create(['event_id' => $event->id]);
    $history2 = EventHistory::factory()->create(['event_id' => $event->id]);

    $result = $event->getHistory();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('id'))->toContain($history1->id, $history2->id);
});

it('returns the most recent approver for the event', function () {
    $event = Event::factory()->create();

    $oldApprover = User::factory()->create();
    $newApprover = User::factory()->create();

    EventHistory::factory()->create([
        'event_id' => $event->id,
        'approver_id' => $oldApprover->id,
        'created_at' => now()->subDay(),
    ]);

    EventHistory::factory()->create([
        'event_id' => $event->id,
        'approver_id' => $newApprover->id,
        'created_at' => now(),
    ]);

    $result = $event->getCurrentApprover();

    expect($result->id)->toBe($newApprover->id);
});
