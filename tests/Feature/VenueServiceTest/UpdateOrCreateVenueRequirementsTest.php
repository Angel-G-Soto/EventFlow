<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\UseRequirement;
use App\Services\VenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates requirements for a venue', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create();
    $data = [
        'documents' => [
            [
                'name' => 'Food Certificate',
                'description' => 'Authorization to sell food.',
                'template_url' => 'https://example.com/insurance.pdf'
            ]
        ],
        'checkboxes' => [
            [
                'label' => 'You agree to not sell or consume alcohol beverages'
            ]
        ]];

    VenueService::updateOrCreateVenueRequirements($venue, $data, $manager);

    expect(UseRequirement::count())->toBe(2);

    $document = UseRequirement::whereNotNull('ur_document_link')->first();
    expect($document)->not()->toBeNull()
        ->and($document->venue_id)->toBe($venue->id)
        ->and($document->ur_name)->toBe($data['documents'][0]['name'])
        ->and($document->ur_description)->toBe($data['documents'][0]['description'])
        ->and($document->ur_document_link)->toBe($data['documents'][0]['template_url']);

    $checkbox = UseRequirement::whereNotNull('ur_label')->first();
    expect($checkbox)->not()->toBeNull()
        ->and($checkbox->ur_label)->toBe($data['checkboxes'][0]['label']);
});

it('deletes old requirements before inserting new ones', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create();

    // Insert old requirements
    UseRequirement::factory()->create([
        'venue_id' => $venue->id,
        'ur_label' => 'Old rule'
    ]);

    expect(UseRequirement::where('venue_id', $venue->id)->count())->toBe(1);

    $data = [
        'documents' => [
            [
                'name' => 'Food Certificate',
                'description' => 'Authorization to sell food.',
                'template_url' => 'https://example.com/insurance.pdf'
            ]
        ],
        'checkboxes' => [
            [
                'label' => 'You agree to not sell or consume alcohol beverages'
            ]
        ]];

    VenueService::updateOrCreateVenueRequirements($venue, $data, $manager);

    $requirements = UseRequirement::where('venue_id', $venue->id)->get();
    expect($requirements)->toHaveCount(2);
    expect($requirements->pluck('ur_label'))->not()->toContain('Old rule');
});

it('handles empty documents and only creates checkboxes', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create();

    $data = [
        'documents' => [],
        'checkboxes' => [
            ['label' => 'No alcohol allowed']
        ]
    ];

    VenueService::updateOrCreateVenueRequirements($venue, $data, $manager);

    expect(UseRequirement::count())->toBe(1);
    expect(UseRequirement::first()->ur_label)->toBe($data['checkboxes'][0]['label']);
});

it('handles empty checkboxes and only creates documents', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create();

    $data = [
        'documents' => [
            [
                'name' => 'Safety Form',
                'description' => 'To be submitted before event.',
                'template_url' => 'https://example.com/safety.pdf'
            ]
        ],
        'checkboxes' => []
    ];

    VenueService::updateOrCreateVenueRequirements($venue, $data, $manager);

    expect(UseRequirement::count())->toBe(1);
    $doc = UseRequirement::first();
    expect($doc->ur_name)->toBe($data['documents'][0]['name']);
});

it('throws exception if something goes wrong', function () {
    $venue = Venue::factory()->create();
    $manager = User::factory()->create();

    // Missing required keys to simulate failure
    $badData = [
        'documents' => [
            ['description' => 'No name or URL'] // missing 'name' and 'template_url'
        ]
    ];

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Unable to update or create the venue requirements.');

    VenueService::updateOrCreateVenueRequirements($venue, $badData, $manager);
});
