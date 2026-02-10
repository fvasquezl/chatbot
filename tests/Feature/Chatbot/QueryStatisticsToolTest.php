<?php

use App\Ai\Tools\QueryStatistics;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tool = new QueryStatistics;
});

it('returns overview statistics', function () {
    $user = User::factory()->create();
    Order::factory(3)->create(['user_id' => $user->id, 'total' => 100.00]);
    Product::factory(5)->create();

    $result = json_decode($this->tool->handle(new Request(['type' => 'overview'])), true);

    expect((float) $result['total_revenue'])->toBe(300.0)
        ->and($result['total_orders'])->toBe(3)
        ->and($result['total_products'])->toBe(5)
        ->and($result['total_customers'])->toBe(1)
        ->and((float) $result['average_order_value'])->toBe(100.0);
});

it('returns top products by sales', function () {
    $product = Product::factory()->create(['name' => 'Popular Item']);
    $order = Order::factory()->create();
    $order->products()->attach($product->id, ['quantity' => 10, 'unit_price' => 25.00]);

    $result = json_decode($this->tool->handle(new Request(['type' => 'top_products'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Popular Item')
        ->and((int) $result[0]['total_sold'])->toBe(10);
});

it('returns top customers by spending', function () {
    $user = User::factory()->create(['name' => 'Big Spender']);
    Order::factory(3)->create(['user_id' => $user->id, 'total' => 500.00]);

    $result = json_decode($this->tool->handle(new Request(['type' => 'top_customers'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Big Spender')
        ->and((float) $result[0]['total_spent'])->toBe(1500.0);
});

it('returns low stock products', function () {
    Product::factory()->create(['name' => 'Almost Out', 'stock' => 2]);
    Product::factory()->create(['name' => 'Plenty', 'stock' => 100]);

    $result = json_decode($this->tool->handle(new Request(['type' => 'low_stock'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Almost Out')
        ->and($result[0]['stock'])->toBe(2);
});

it('returns revenue by status', function () {
    Order::factory(2)->create(['status' => OrderStatus::Delivered, 'total' => 100.00]);
    Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 50.00]);

    $result = json_decode($this->tool->handle(new Request(['type' => 'revenue_by_status'])), true);

    $delivered = collect($result)->firstWhere('status', 'delivered');
    $pending = collect($result)->firstWhere('status', 'pending');

    expect($delivered['order_count'])->toBe(2)
        ->and((float) $delivered['total_revenue'])->toBe(200.0)
        ->and($pending['order_count'])->toBe(1)
        ->and((float) $pending['total_revenue'])->toBe(50.0);
});

it('returns error for unknown statistic type', function () {
    $result = $this->tool->handle(new Request(['type' => 'unknown']));

    expect($result)->toContain('Unknown statistic type');
});

it('returns empty messages when no data exists', function () {
    $result = $this->tool->handle(new Request(['type' => 'top_products']));

    expect($result)->toBe('No product sales data available.');
});
