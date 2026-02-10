<?php

use App\Ai\Tools\QueryOrders;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tool = new QueryOrders;
});

it('returns all orders when no filters are provided', function () {
    Order::factory(3)->create();

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result)->toHaveCount(3);
});

it('filters orders by status', function () {
    Order::factory(2)->create(['status' => OrderStatus::Pending]);
    Order::factory()->create(['status' => OrderStatus::Delivered]);

    $result = json_decode($this->tool->handle(new Request(['status' => 'pending'])), true);

    expect($result)->toHaveCount(2)
        ->and($result[0]['status'])->toBe('pending');
});

it('filters orders by user', function () {
    $user = User::factory()->create();
    Order::factory(2)->create(['user_id' => $user->id]);
    Order::factory()->create();

    $result = json_decode($this->tool->handle(new Request(['user_id' => $user->id])), true);

    expect($result)->toHaveCount(2);
});

it('filters orders by date range', function () {
    Order::factory()->create(['created_at' => '2026-01-01']);
    Order::factory()->create(['created_at' => '2026-01-15']);
    Order::factory()->create(['created_at' => '2026-02-01']);

    $result = json_decode($this->tool->handle(new Request([
        'date_from' => '2026-01-10',
        'date_to' => '2026-01-20',
    ])), true);

    expect($result)->toHaveCount(1);
});

it('filters orders by total range', function () {
    Order::factory()->create(['total' => 50.00]);
    Order::factory()->create(['total' => 150.00]);
    Order::factory()->create(['total' => 300.00]);

    $result = json_decode($this->tool->handle(new Request(['min_total' => 100, 'max_total' => 200])), true);

    expect($result)->toHaveCount(1)
        ->and((float) $result[0]['total'])->toBe(150.00);
});

it('includes products in order results', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['name' => 'Test Widget']);

    $order->products()->attach($product->id, ['quantity' => 3, 'unit_price' => 10.00]);

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result[0]['products'])->toHaveCount(1)
        ->and($result[0]['products'][0]['name'])->toBe('Test Widget')
        ->and($result[0]['products'][0]['quantity'])->toBe(3);
});

it('includes user information in results', function () {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    Order::factory()->create(['user_id' => $user->id]);

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result[0]['user'])->toBe('Jane Doe')
        ->and($result[0]['user_email'])->toBe('jane@example.com');
});

it('returns a message when no orders match', function () {
    $result = $this->tool->handle(new Request(['status' => 'pending']));

    expect($result)->toBe('No orders found matching the criteria.');
});

it('ignores invalid status values', function () {
    Order::factory(2)->create();

    $result = json_decode($this->tool->handle(new Request(['status' => 'invalid'])), true);

    expect($result)->toHaveCount(2);
});
