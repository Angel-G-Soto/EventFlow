<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new CategoryService();
});

it('deletes a category successfully', function () {
    $category = Category::create(['name' => 'Food Sale']);

    $result = $this->service->deleteCategory($category->id);

    expect($result)->toBeTrue();
    expect(Category::find($category->id))->toBeNull();
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->service->deleteCategory(-1);
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent ID', function () {
    $this->service->deleteCategory(999999);
})->throws(ModelNotFoundException::class);
