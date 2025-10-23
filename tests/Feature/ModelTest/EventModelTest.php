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
        'organization_nexo_id' => 999,
        'organization_nexo_name' => 'Order of the Phoenix',
        'organization_advisor_name' => 'Prof. Dumbledore',
        'organization_advisor_email' => 'albus.dumbledore@hogwarts.com',
        'organization_advisor_phone' => '787-832-4040',
        'title' => 'Phoenix Feathers',
        'description' => 'Discussion on phoenix feathers.',
        'status' => 'pending',
        'start_time' => now(),
        'end_time' => now()->addHours(2),
        'student_number' => '123456',
        'student_phone' => '787-832-4040',
        'guests' => 50,
        'handles_food' => false,
        'use_institutional_funds' => true,
        'external_guest' => false,
    ];

    $event = Event::create($data);

    foreach ($data as $key => $value) {
        expect($event->{$key})->toEqual($value);
    }
});
