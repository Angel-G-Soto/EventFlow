<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new CategoryService();
});

it('updates category name successfully', function () {
    $category = Category::create(['name' => 'Food Sale']);

    $updated = $this->service->updateCategory($category->id, 'Updated Name', 'Valid justification text');

    expect($updated->name)->toBe('Updated Name');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
    ]);
});

it('throws InvalidArgumentException for negative ID', function () {
    $this->service->updateCategory(-1, 'Food Sale', 'Valid justification text');
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException for non-existent category ID', function () {
    $this->service->updateCategory(999999, 'Food Sale', 'Valid justification text');
})->throws(ModelNotFoundException::class);

it('throws InvalidArgumentException for short justification', function () {
    $category = Category::create(['name' => 'Another']);
    try {
        $this->service->updateCategory($category->id, 'New Name', 'short');
    } finally {
        $category->delete();
    }
})->throws(InvalidArgumentException::class, 'Justification must be at least 10 characters.');
