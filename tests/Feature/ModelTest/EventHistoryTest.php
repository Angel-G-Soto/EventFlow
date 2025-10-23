<?php

use App\Models\EventHistory;
use App\Models\Event;
use App\Models\User;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $history = EventHistory::factory()->create(['event_id' => $event->id]);

    expect($history->event)->toBeInstanceOf(Event::class)
        ->and($history->event->id)->toBe($event->id);
});

it('belongs to a user as approver', function () {
    $user = User::factory()->create();
    $history = EventHistory::factory()->create(['approver_id' => $user->id]);

    expect($history->approver)->toBeInstanceOf(User::class)
        ->and($history->approver->id)->toBe($user->id);
});

it('allows mass assignment of fillable fields', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $data = [
        'event_id' => $event->id,
        'approver_id' => $user->id,
        'action' => 'approved',
        'comment' => 'Approved by admin',
    ];

    $history = EventHistory::create($data);

    foreach ($data as $key => $value) {
        expect($history->{$key})->toEqual($value);
    }
});
