<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Models\Category;
use App\Models\Document;
use App\Models\EventRequestHistory;

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
    EventRequestHistory::factory()->count(2)->create(['event_id' => $event->id]);

    expect($event->history)->toHaveCount(2)
        ->each->toBeInstanceOf(EventRequestHistory::class);
});

it('belongs to many categories', function () {
    $event = Event::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $event->categories()->attach($categories->pluck('id'));

    expect($event->categories)->toHaveCount(2)
        ->each->toBeInstanceOf(Category::class);
});

it('allows mass assignment of fillable fields', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $venue = Venue::factory()->create();
    $data = [
        'creator_id' => $user1->id,
        'current_approver_id' => $user2->id,
        'venue_id' => $venue->id,
        'e_organization_nexo_id' => 999,
        'e_advisor_name' => 'Prof. Dumbledore',
        'e_advisor_email' => 'albus.dumbledore@hogwarts.com',
        'e_advisor_phone' => '787-832-4040',
        'e_organization_name' => 'Magic CLub',
        'e_title' => 'Phoenix Feathers',
        'e_description' => 'Discussion on phoenix feathers.',
        'e_status' => 'pending',
        'e_status_code' => 'P',
        'e_upload_status' => 'uploaded',
        'e_start_time' => now(),
        'e_end_time' => now()->addHours(2),
        'e_student_id' => '123456',
        'e_student_phone' => '787-832-4040',
        'e_guests' => 50,
    ];

    $event = Event::create($data);

    foreach ($data as $key => $value) {
        expect($event->{$key})->toEqual($value);
    }
});
