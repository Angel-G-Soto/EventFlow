<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new CategoryService();
});

it('returns the category if the ID exists', function () {
    $category = Category::factory()->create();

    $result = $this->service->getCategoryByID($category->id);

    expect($result->id)->toBe($category->id);
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->service->getCategoryByID(-5);
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent ID', function () {
    $this->service->getCategoryByID(999);
})->throws(ModelNotFoundException::class);
