<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;

it('belongs to many events', function () {
    $category = Category::factory()->create();
    $events = Event::factory()->count(2)->create();

    $category->events()->attach($events->pluck('id'));

    expect($category->events)->toHaveCount(2)
        ->each->toBeInstanceOf(Event::class);
});

it('belongs to many venues', function () {
    $category = Category::factory()->create();
    $venues = Venue::factory()->count(3)->create();

    $category->venues()->attach($venues->pluck('id'));

    expect($category->venues)->toHaveCount(3)
        ->each->toBeInstanceOf(Venue::class);
});

it('allows mass assignment of fillable fields', function () {
    $event = Event::factory()->create();
    $data = [
        'c_name' => 'Food Sales',
    ];

    $category = Category::create($data);

    foreach ($data as $key => $value) {
        expect($category->{$key})->toEqual($value);
    }
});
