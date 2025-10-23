<?php

use App\Models\Category;
use App\Services\CategoryService;

it('returns all categories', function () {
    Category::factory()->count(3)->create();

    $result = CategoryService::getAllCategories();

    expect($result)->toHaveCount(3)
        ->and($result->first())->toBeInstanceOf(Category::class);
});

it('returns an empty collection if no categories exist', function () {
    $result = CategoryService::getAllCategories();

    expect($result)->toBeEmpty();
});

