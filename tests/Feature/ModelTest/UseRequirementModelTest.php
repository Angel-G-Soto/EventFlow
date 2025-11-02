<?php

use App\Models\UseRequirement;
use App\Models\Venue;
use App\Models\Department;

it('belongs to a venue', function () {
    $venue = Venue::factory()->create();
    $requirement = UseRequirement::factory()->create([
        'venue_id' => $venue->id,
    ]);

    expect($requirement->venue)->toBeInstanceOf(Venue::class)
        ->and($requirement->venue->id)->toBe($venue->id);
});

it('allows mass assignment of fillable fields', function () {
    $department = Department::factory()->create();
    $venue = Venue::factory()->create();

    $data = [
        'venue_id' => $venue->id,
        'hyperlink' => 'https://cdc.gov/doc.pdf',
        'name' => 'COVID-19 Compliance',
        'description' => 'Ensure sanitization before and after use.',
    ];

    $requirement = UseRequirement::create($data);

    foreach ($data as $key => $value) {
        expect($requirement->{$key})->toEqual($value);
    }
});
