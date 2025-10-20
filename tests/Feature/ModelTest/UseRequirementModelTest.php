<?php

use App\Models\UseRequirement;
use App\Models\Venue;
use App\Models\Department;

it('belongs to a department', function () {
    $department = Department::factory()->create();
    $requirement = UseRequirement::factory()->create([
        'department_id' => $department->id,
    ]);

    expect($requirement->department)->toBeInstanceOf(Department::class)
        ->and($requirement->department->id)->toBe($department->id);
});

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
        'department_id' => $department->id,
        'venue_id' => $venue->id,
        'ur_document_link' => 'https://cdc.gov/doc.pdf',
        'ur_name' => 'COVID-19 Compliance',
        'ur_description' => 'Ensure sanitization before and after use.',
        'ur_label' => 'Health',
    ];

    $requirement = UseRequirement::create($data);

    foreach ($data as $key => $value) {
        expect($requirement->{$key})->toEqual($value);
    }
});
