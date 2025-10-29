<?php

use App\Models\Category;
use App\Models\Event;

it('belongs to many events', function () {
    $category = Category::factory()->create();
    $events = Event::factory()->count(2)->create();

    $category->events()->attach($events->pluck('id'));

    expect($category->events)->toHaveCount(2)
        ->each->toBeInstanceOf(Event::class);
});

it('allows mass assignment of fillable fields', function () {
    $event = Event::factory()->create();
    $data = [
        'name' => 'Food Sales',
    ];

    $category = Category::create($data);

    foreach ($data as $key => $value) {
        expect($category->{$key})->toEqual($value);
    }
});
