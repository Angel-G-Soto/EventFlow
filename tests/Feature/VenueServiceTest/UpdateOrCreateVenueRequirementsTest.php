<?php

use App\Models\Venue;
use App\Models\User;
use App\Models\UseRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\VenueService;

it('successfully creates venue requirements', function () {

    $venue = Venue::factory()->create();
    $manager = User::factory()->create();

    $hyperlink = 'http://example.com';
    $instructions = 'Follow the guidelines.';
    $alcohol_policy = true;
    $cleanup_policy = false;

    $updatedVenue = VenueService::updateOrCreateVenueRequirements(
        $venue,
        $hyperlink,
        $instructions,
        $alcohol_policy,
        $cleanup_policy,
        $manager
    );

    expect($updatedVenue->requirements)->toBeInstanceOf(UseRequirement::class);
    expect($updatedVenue->requirements->us_doc_drive)->toBe($hyperlink);
    expect($updatedVenue->requirements->us_instructions)->toBe($instructions);
    expect((bool)$updatedVenue->requirements->us_alcohol_policy)->toBe($alcohol_policy);
    expect((bool)$updatedVenue->requirements->us_cleanup_policy)->toBe($cleanup_policy);

    $updatedVenue->refresh();
    expect($updatedVenue->use_requirement_id)->toBe($updatedVenue->requirements->id);
});

it('successfully updates venue requirements', function () {

    $venue = Venue::factory()->create();
    $manager = User::factory()->create();
    $existingRequirement = UseRequirements::factory()->create();

    $venue->use_requirement_id = $existingRequirement->id;
    $venue->save();

    $newHyperlink = 'http://updated-example.com';
    $newInstructions = 'Updated instructions for the venue.';
    $newAlcoholPolicy = false;
    $newCleanupPolicy = true;

    $updatedVenue = VenueService::updateOrCreateVenueRequirements(
        $venue,
        $newHyperlink,
        $newInstructions,
        $newAlcoholPolicy,
        $newCleanupPolicy,
        $manager
    );

    expect($updatedVenue->requirements)->toBeInstanceOf(UseRequirements::class);
    expect($updatedVenue->requirements->us_doc_drive)->toBe($newHyperlink);
    expect($updatedVenue->requirements->us_instructions)->toBe($newInstructions);
    expect((bool)$updatedVenue->requirements->us_alcohol_policy)->toBe($newAlcoholPolicy);
    expect((bool)$updatedVenue->requirements->us_cleanup_policy)->toBe($newCleanupPolicy);

    $updatedVenue->refresh();
    expect($updatedVenue->use_requirement_id)->toBe($updatedVenue->requirements->id);
});
