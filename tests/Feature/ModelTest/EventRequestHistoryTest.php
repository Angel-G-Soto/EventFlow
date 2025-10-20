<?php

use App\Models\EventRequestHistory;
use App\Models\Event;
use App\Models\User;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $history = EventRequestHistory::factory()->create(['event_id' => $event->id]);

    expect($history->event)->toBeInstanceOf(Event::class)
        ->and($history->event->id)->toBe($event->id);
});

it('belongs to a user as approver', function () {
    $user = User::factory()->create();
    $history = EventRequestHistory::factory()->create(['user_id' => $user->id]);

    expect($history->approver)->toBeInstanceOf(User::class)
        ->and($history->approver->id)->toBe($user->id);
});

it('allows mass assignment of fillable fields', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $data = [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'eh_action' => 'approved',
        'eh_comment' => 'Approved by admin',
    ];

    $history = EventRequestHistory::create($data);

    foreach ($data as $key => $value) {
        expect($history->{$key})->toEqual($value);
    }
});
