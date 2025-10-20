<?php

use App\Models\Document;
use App\Models\Event;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $document = Document::factory()->create(['event_id' => $event->id]);

    expect($document->event)->toBeInstanceOf(Event::class)
        ->and($document->event->id)->toBe($event->id);
});

it('allows mass assignment of fillable fields', function () {
    $event = Event::factory()->create();
    $data = [
        'event_id' => $event->id,
        'd_name' => 'Required_Document.pdf',
        'd_file_path' => 'documents/required-document.pdf',
    ];

    $document = Document::create($data);

    foreach ($data as $key => $value) {
        expect($document->{$key})->toEqual($value);
    }
});
