<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('returns requirements for a valid category', function () {
    $category = Category::create(['c_name' => 'Food Sale']);

    $category->requirements()->createMany([
        ['ur_name' => 'Food Certificate'],
        ['ur_name' => 'Insurance'],
    ]);

    $requirements = CategoryService::getUseRequirement($category->id);

    expect($requirements)->toHaveCount(2);
    expect($requirements->pluck('ur_name'))->toContain('Food Certificate', 'Insurance');
});

it('throws InvalidArgumentException for negative ID', function () {
    CategoryService::getUseRequirement(-1);
})->throws(InvalidArgumentException::class, 'Category ID must be a positive integer.');

it('throws ModelNotFoundException if category does not exist', function () {
    CategoryService::getUseRequirement(999999);
})->throws(ModelNotFoundException::class);
