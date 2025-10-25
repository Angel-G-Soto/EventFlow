<?php

use App\Models\Venue;
use App\Models\User;
use App\Models\Department;
use App\Services\VenueService;
use App\Services\DepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new VenueService(new DepartmentService());
});

it('returns all venues if no filters are provided', function () {
    Venue::factory()->count(5)->create();

    $results = $this->service->getAllVenues();

    expect($results->total())->toBe(5);
});

it('applies manager_id filter', function () {
    $manager1 = User::factory()->create();
    $manager2 = User::factory()->create();

    $venue1 = Venue::factory()->create(['manager_id' => $manager1->id]);
    $venue2 = Venue::factory()->create(['manager_id' => $manager2->id]);

    $results = $this->service->getAllVenues(['manager_id' => $manager1->id]);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});

it('applies department_id filter', function () {
    $dept1 = Department::factory()->create();
    $dept2 = Department::factory()->create();

    $venue1 = Venue::factory()->create(['department_id' => $dept1->id]);
    $venue2 = Venue::factory()->create(['department_id' => $dept2->id]);

    $results = $this->service->getAllVenues(['department_id' => $dept1->id]);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});

it('applies name and code filters', function () {
    $venue1 = Venue::factory()->create(['name' => 'SALON DE CLASES', 'code' => 'AE-102']);
    $venue2 = Venue::factory()->create(['name' => 'COMPUTADORAS', 'code' => 'AE-111']);

    $results = $this->service->getAllVenues(['name' => 'SALON DE CLASES', 'code' => 'AE-102']);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});

it('applies features filter', function () {
    $venue1 = Venue::factory()->create(['features' => '0010']);
    $venue2 = Venue::factory()->create(['features' => '0001']);

    $results = $this->service->getAllVenues(['features' => '0010']);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});

it('applies capacity and test_capacity filters', function () {
    $venue1 = Venue::factory()->create(['capacity' => 50, 'test_capacity' => 40]);
    $venue2 = Venue::factory()->create(['capacity' => 30, 'test_capacity' => 20]);

    $results = $this->service->getAllVenues(['capacity' => 40, 'test_capacity' => 30]);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});

it('applies opening_time and closing_time filters', function () {
    $venue1 = Venue::factory()->create(['opening_time' => '09:00:00', 'closing_time' => '18:00:00']);
    $venue2 = Venue::factory()->create(['opening_time' => '10:00:00', 'closing_time' => '19:00:00']);

    $results = $this->service->getAllVenues(['opening_time' => '09:00:00', 'closing_time' => '18:00:00']);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($venue1->id);
});


it('throws InvalidArgumentException for invalid filter keys', function () {
    $filters = [
        'invalid_key' => 'test',
        'name' => 'Venue A'
    ];

    $this->service->getAllVenues($filters);

})->throws(InvalidArgumentException::class, 'Invalid attribute keys detected: invalid_key');

it('throws InvalidArgumentException for null values in filters', function () {
    $filters = [
        'manager_id' => null,
        'name' => 'Venue A'
    ];

    $this->service->getAllVenues($filters);

})->throws(InvalidArgumentException::class, 'Null values are not allowed for keys: manager_id');

it('does not throw exception for valid filters', function () {
    $filters = [
        'manager_id' => 1,
        'department_id' => 2,
        'name' => 'Venue A',
        'code' => 'V001',
        'features' => 'Feature A',
        'capacity' => 50,
        'test_capacity' => 10,
        'opening_time' => '08:00:00',
        'closing_time' => '18:00:00'
    ];

    $results = $this->service->getAllVenues($filters);

    expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
});
