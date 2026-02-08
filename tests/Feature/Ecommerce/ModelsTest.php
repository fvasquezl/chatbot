<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;

// --- Creation ---

it('creates a category', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->name)->toBeString();
});

it('creates a product', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->toBeString()
        ->and($product->description)->toBeString()
        ->and((float) $product->price)->toBeGreaterThan(0)
        ->and($product->stock)->toBeGreaterThanOrEqual(0);
});

it('creates an order', function () {
    $order = Order::factory()->create();

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBeInstanceOf(OrderStatus::class)
        ->and((float) $order->total)->toBeGreaterThan(0);
});

// --- Relationships ---

it('category has many products', function () {
    $category = Category::factory()->create();
    Product::factory(3)->create(['category_id' => $category->id]);

    expect($category->products)->toHaveCount(3)
        ->each->toBeInstanceOf(Product::class);
});

it('product belongs to category', function () {
    $product = Product::factory()->create();

    expect($product->category)->toBeInstanceOf(Category::class);
});

it('order belongs to user', function () {
    $order = Order::factory()->create();

    expect($order->user)->toBeInstanceOf(User::class);
});

it('user has many orders', function () {
    $user = User::factory()->create();
    Order::factory(2)->create(['user_id' => $user->id]);

    expect($user->orders)->toHaveCount(2)
        ->each->toBeInstanceOf(Order::class);
});

it('order belongs to many products', function () {
    $order = Order::factory()->create();
    $products = Product::factory(2)->create();

    $order->products()->attach($products->pluck('id')->mapWithKeys(fn ($id) => [
        $id => ['quantity' => 1, 'unit_price' => 10.00],
    ]));

    expect($order->products)->toHaveCount(2)
        ->each->toBeInstanceOf(Product::class);
});

it('product belongs to many orders', function () {
    $product = Product::factory()->create();
    $orders = Order::factory(2)->create();

    $orders->each(fn (Order $order) => $order->products()->attach($product->id, [
        'quantity' => 1,
        'unit_price' => $product->price,
    ]));

    expect($product->orders)->toHaveCount(2)
        ->each->toBeInstanceOf(Order::class);
});

// --- Pivot data ---

it('stores quantity and unit_price in pivot table', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $order->products()->attach($product->id, [
        'quantity' => 3,
        'unit_price' => 25.50,
    ]);

    $pivot = $order->products->first()->pivot;

    expect($pivot->quantity)->toBe(3)
        ->and((float) $pivot->unit_price)->toBe(25.50);
});

it('stores timestamps in pivot table', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $order->products()->attach($product->id, [
        'quantity' => 1,
        'unit_price' => 10.00,
    ]);

    $pivot = $order->products->first()->pivot;

    expect($pivot->created_at)->not->toBeNull()
        ->and($pivot->updated_at)->not->toBeNull();
});

// --- RESTRICT constraints ---

it('prevents deleting a category with products', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $category->delete();
})->throws(QueryException::class);

it('prevents deleting a user with orders', function () {
    $user = User::factory()->create();
    Order::factory()->create(['user_id' => $user->id]);

    $user->delete();
})->throws(QueryException::class);

it('prevents deleting a product attached to orders', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();

    $order->products()->attach($product->id, [
        'quantity' => 1,
        'unit_price' => $product->price,
    ]);

    $product->delete();
})->throws(QueryException::class);

// --- CASCADE constraint ---

it('deletes order_product records when order is deleted', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $order->products()->attach($product->id, [
        'quantity' => 1,
        'unit_price' => $product->price,
    ]);

    expect($order->products)->toHaveCount(1);

    $order->delete();

    $this->assertDatabaseMissing('order_product', ['order_id' => $order->id]);
});

// --- Enum cast ---

it('casts status to OrderStatus enum', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Pending);
});
