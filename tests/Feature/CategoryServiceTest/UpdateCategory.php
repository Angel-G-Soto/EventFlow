<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('updates category name successfully', function () {
    $category = Category::create(['c_name' => 'Food Sale']);

    $updated = CategoryService::updateCategory($category->id, 'Updated Name');

    expect($updated->c_name)->toBe('Updated Name');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'c_name' => 'Updated Name',
    ]);
});

it('throws InvalidArgumentException for negative ID', function () {
    CategoryService::updateCategory(-1, 'Food Sale');
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent category ID', function () {
    CategoryService::updateCategory(999999, 'Food Sale');
})->throws(ModelNotFoundException::class);
