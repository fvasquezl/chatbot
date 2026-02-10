<?php

use App\Ai\Tools\QueryProducts;
use App\Models\Category;
use App\Models\Product;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tool = new QueryProducts;
});

it('returns all products when no filters are provided', function () {
    Product::factory(3)->create();

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result)->toHaveCount(3);
});

it('searches products by name', function () {
    Product::factory()->create(['name' => 'Wireless Keyboard']);
    Product::factory()->create(['name' => 'Wired Mouse']);

    $result = json_decode($this->tool->handle(new Request(['search' => 'Keyboard'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Wireless Keyboard');
});

it('searches products by description', function () {
    Product::factory()->create(['description' => 'A fast gaming laptop']);
    Product::factory()->create(['description' => 'A simple notebook']);

    $result = json_decode($this->tool->handle(new Request(['search' => 'gaming'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['description'])->toContain('gaming');
});

it('filters products by category', function () {
    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $books = Category::factory()->create(['name' => 'Books']);

    Product::factory(2)->create(['category_id' => $electronics->id]);
    Product::factory()->create(['category_id' => $books->id]);

    $result = json_decode($this->tool->handle(new Request(['category_id' => $electronics->id])), true);

    expect($result)->toHaveCount(2)
        ->and($result[0]['category'])->toBe('Electronics');
});

it('filters products by price range', function () {
    Product::factory()->create(['price' => 10.00]);
    Product::factory()->create(['price' => 50.00]);
    Product::factory()->create(['price' => 100.00]);

    $result = json_decode($this->tool->handle(new Request(['min_price' => 20, 'max_price' => 80])), true);

    expect($result)->toHaveCount(1)
        ->and((float) $result[0]['price'])->toBe(50.00);
});

it('filters low stock products', function () {
    Product::factory()->create(['stock' => 5]);
    Product::factory()->create(['stock' => 50]);

    $result = json_decode($this->tool->handle(new Request(['low_stock' => true])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['stock'])->toBeLessThanOrEqual(10);
});

it('returns a message when no products match', function () {
    $result = $this->tool->handle(new Request(['search' => 'nonexistent']));

    expect($result)->toBe('No products found matching the criteria.');
});

it('limits results to 50 products', function () {
    Product::factory(60)->create();

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result)->toHaveCount(50);
});

it('includes category name in results', function () {
    $category = Category::factory()->create(['name' => 'Gadgets']);
    Product::factory()->create(['category_id' => $category->id]);

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result[0])->toHaveKeys(['id', 'name', 'description', 'price', 'stock', 'category'])
        ->and($result[0]['category'])->toBe('Gadgets');
});
