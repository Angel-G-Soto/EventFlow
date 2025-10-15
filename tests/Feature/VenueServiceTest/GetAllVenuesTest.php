<?php

use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    Venue::factory()->create([
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-102',
        'v_features' => '1011',
        'v_capacity' => 100,
        'v_test_capacity' => 90,
    ]);

    Venue::factory()->create([
        'v_name' => 'OFIC. CENTRO DE COMPUTOS',
        'v_code' => 'AE-110',
        'v_features' => '0000',
        'v_capacity' => 40,
        'v_test_capacity' => 35,
    ]);

    Venue::factory()->create([
        'v_name' => 'SALON DE CLASES',
        'v_code' => 'AE-106',
        'v_features' => '1011',
        'v_capacity' => 50,
        'v_test_capacity' => 40,
    ]);
});

it('returns all venues with no filters', function () {
    $results = VenueService::getAllVenues([]);

    expect($results)->toHaveCount(3);
});

it('applies single filter correctly', function () {
    $results = VenueService::getAllVenues(['v_code' => 'AE-110']);

    expect($results)->toHaveCount(1)
        ->and($results->first()->v_name)->toBe('OFIC. CENTRO DE COMPUTOS');
});

it('applies multiple filters correctly', function () {
    $filters = [
        'v_features' => '1011',
        'v_capacity' => 50,
    ];

    $results = VenueService::getAllVenues($filters);

    expect($results)->toHaveCount(1)
        ->and($results->first()->v_code)->toBe('AE-106');
});

it('ignores filters not in fillable fields', function () {
    $filters = [
        'v_name' => 'SALON DE CLASES',
        'non_existent_column' => 'value'
    ];

    $results = VenueService::getAllVenues($filters);

    expect($results)->toHaveCount(2)
        ->and($results->pluck('v_code'))->toContain('AE-102', 'AE-106');
});

it('ignores filters with null values', function () {
    $filters = [
        'v_code' => null,
        'v_name' => 'OFIC. CENTRO DE COMPUTOS',
    ];

    $results = VenueService::getAllVenues($filters);

    expect($results)->toHaveCount(1)
        ->and($results->first()->v_code)->toBe('AE-110');
});

it('returns paginated results when paginate is true', function () {
    // Create additional venues to test pagination
    Venue::factory()->count(20)->create();

    $paginated = VenueService::getAllVenues([]);

    expect($paginated)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginated->count())->toBeLessThanOrEqual(10)
        ->and($paginated->total())->toBeGreaterThanOrEqual(23);
});
