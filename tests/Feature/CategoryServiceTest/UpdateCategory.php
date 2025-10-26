<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new CategoryService();
});

it('updates category name successfully', function () {
    $category = Category::create(['name' => 'Food Sale']);

    $updated = $this->service->updateCategory($category->id, 'Updated Name');

    expect($updated->name)->toBe('Updated Name');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
    ]);
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->service->updateCategory(-1, 'Food Sale');
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent category ID', function () {
    $this->service->updateCategory(999999, 'Food Sale');
})->throws(ModelNotFoundException::class);
