<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can render the products list page', function () {
    $this->get(ProductResource::getUrl('index'))->assertSuccessful();
});

test('can list products', function () {
    $products = Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords($products);
});

test('can search products by name', function () {
    $products = Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)
        ->searchTable($products->first()->name)
        ->assertCanSeeTableRecords($products->where('name', $products->first()->name))
        ->assertCanNotSeeTableRecords($products->where('name', '!=', $products->first()->name));
});

test('can filter products by category', function () {
    $categories = Category::factory()->count(2)->create();
    $productA = Product::factory()->create(['category_id' => $categories->first()->id]);
    $productB = Product::factory()->create(['category_id' => $categories->last()->id]);

    Livewire::test(ListProducts::class)
        ->filterTable('category', $categories->first()->id)
        ->assertCanSeeTableRecords([$productA])
        ->assertCanNotSeeTableRecords([$productB]);
});

test('can render the create product page', function () {
    $this->get(ProductResource::getUrl('create'))->assertSuccessful();
});

test('can create a product', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'category_id' => $category->id,
            'description' => 'A test product description.',
            'price' => 29.99,
            'stock' => 50,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Product::class, [
        'name' => 'Test Product',
        'category_id' => $category->id,
    ]);
});

test('can validate required fields when creating a product', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => null,
            'category_id' => null,
            'description' => null,
            'price' => null,
            'stock' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'category_id' => 'required',
            'description' => 'required',
            'price' => 'required',
            'stock' => 'required',
        ]);
});

test('can render the edit product page', function () {
    $product = Product::factory()->create();

    $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertSuccessful();
});

test('can update a product', function () {
    $product = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Product',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->name)->toBe('Updated Product');
});
