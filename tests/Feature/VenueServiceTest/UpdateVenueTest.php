<?php

use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;

it('updates venue with valid fillable fields', function () {
    $admin = User::factory()->create();
    $venue = Venue::factory()->create([
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-102',
    ]);

    $data = [
        'v_name' => 'OFIC. CENTRO DE COMPUTOS',
        'v_code' => 'AE-110',
    ];

    $result = VenueService::updateVenue($venue, $data, $admin);
    $venue->refresh();

    expect($result)->toBeInstanceOf(Venue::class)
        ->and($result->v_name)->toBe($data['v_name'])
        ->and($result->v_code)->toBe($data['v_code'])
        ->and($venue->v_name)->toBe($data['v_name'])
        ->and($venue->v_code)->toBe($data['v_code']);
});

it('ignores null values in update data', function () {
    $admin = User::factory()->create();
    $venue = Venue::factory()->create([
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-102',
    ]);

    $data = [
        'v_name' => null,
        'v_code' => 'AE-106',
    ];

    $result = VenueService::updateVenue($venue, $data, $admin);
    $venue->refresh();

    expect($result)->toBeInstanceOf(Venue::class)
        ->and($result->v_name)->toBe('SALON DE CLASES')
        ->and($result->v_code)->toBe($data['v_code'])
        ->and($venue->v_name)->toBe('SALON DE CLASES')
        ->and($venue->v_code)->toBe($data['v_code']);
});

it('ignores non-fillable keys in data', function () {
    $admin = User::factory()->create();
    $venue = Venue::factory()->create([
        'v_name' => 'Original',
    ]);

    $data = [
        'v_name' => 'Changed',
        'not_a_column' => 'Ignored',
    ];

    $result = VenueService::updateVenue($venue, $data, $admin);
    $venue->refresh();

    expect($result)->toBeInstanceOf(Venue::class)
        ->and($result->v_name)->toBe($data['v_name'])
        ->and($result)->not()->toHaveProperty('not_a_column')
        ->and($venue->v_name)->toBe($data['v_name'])
        ->and($venue)->not()->toHaveProperty('not_a_column');
    });

it('does not change anything when all values are null or non-fillable', function () {
    $admin = User::factory()->create();
    $venue = Venue::factory()->create([
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-102',
    ]);

    $data = [
        'v_name' => null,
        'non_fillable_field' => 'irrelevant'
    ];

    $result = VenueService::updateVenue($venue, $data, $admin);
    $venue->refresh();

    expect($result)->toBeInstanceOf(Venue::class)
        ->and($result->v_name)->toBe('SALON DE CLASES')
        ->and($result->v_code)->toBe('AE-102')
        ->and($venue->v_name)->toBe('SALON DE CLASES')
        ->and($venue->v_code)->toBe('AE-102');
    });

