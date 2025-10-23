<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('returns the category if the ID exists', function () {
    $category = Category::factory()->create();

    $result = CategoryService::getCategoryByID($category->id);

    expect($result->id)->toBe($category->id);
});

it('throws InvalidArgumentException for negative ID', function () {
    CategoryService::getCategoryByID(-5);
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent ID', function () {
    CategoryService::getCategoryByID(999);
})->throws(ModelNotFoundException::class);
