<?php

use App\Ai\Tools\QueryCategories;
use App\Models\Category;
use App\Models\Product;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tool = new QueryCategories;
});

it('returns all categories with product counts', function () {
    $category = Category::factory()->create(['name' => 'Electronics']);
    Product::factory(3)->create(['category_id' => $category->id]);

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Electronics')
        ->and($result[0]['products_count'])->toBe(3);
});

it('searches categories by name', function () {
    Category::factory()->create(['name' => 'Electronics']);
    Category::factory()->create(['name' => 'Books']);

    $result = json_decode($this->tool->handle(new Request(['search' => 'Elec'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Electronics');
});

it('returns a message when no categories match', function () {
    $result = $this->tool->handle(new Request(['search' => 'nonexistent']));

    expect($result)->toBe('No categories found matching the criteria.');
});

it('includes id, name, and products_count in results', function () {
    Category::factory()->create();

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result[0])->toHaveKeys(['id', 'name', 'products_count']);
});

it('shows zero product count for empty categories', function () {
    Category::factory()->create(['name' => 'Empty Category']);

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result[0]['products_count'])->toBe(0);
});
