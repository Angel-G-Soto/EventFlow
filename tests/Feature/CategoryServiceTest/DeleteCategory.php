<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('deletes a category successfully', function () {
    $category = Category::create(['c_name' => 'Food Sale']);

    $result = CategoryService::deleteCategory($category->id);

    expect($result)->toBeTrue();
    expect(Category::find($category->id))->toBeNull();
});

it('throws InvalidArgumentException for negative ID', function () {
    CategoryService::deleteCategory(-1);
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent ID', function () {
    CategoryService::deleteCategory(999999);
})->throws(ModelNotFoundException::class);
