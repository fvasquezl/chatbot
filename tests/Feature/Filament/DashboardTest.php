<?php

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopProducts;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can render the admin dashboard', function () {
    $this->get('/admin')->assertSuccessful();
});

test('stats overview widget displays stats', function () {
    Order::factory()->count(3)->create(['total' => 100.00]);
    Product::factory()->count(5)->create();

    Livewire::test(StatsOverview::class)
        ->assertSeeHtml('$300.00')
        ->assertSeeHtml('3')
        ->assertSeeHtml('5');
});

test('orders chart widget can render', function () {
    Livewire::test(OrdersChart::class)
        ->assertSuccessful();
});

test('latest orders widget displays recent orders', function () {
    $orders = Order::factory()->count(5)->create();

    Livewire::test(LatestOrders::class)
        ->assertCanSeeTableRecords($orders);
});

test('top products widget can render', function () {
    Livewire::test(TopProducts::class)
        ->assertSuccessful();
});
