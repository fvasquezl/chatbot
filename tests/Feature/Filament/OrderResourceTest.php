<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can render the orders list page', function () {
    $this->get(OrderResource::getUrl('index'))->assertSuccessful();
});

test('can list orders', function () {
    $orders = Order::factory()->count(3)->create();

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords($orders);
});

test('can filter orders by status', function () {
    $pendingOrder = Order::factory()->create(['status' => OrderStatus::Pending]);
    $deliveredOrder = Order::factory()->create(['status' => OrderStatus::Delivered]);

    Livewire::test(ListOrders::class)
        ->filterTable('status', OrderStatus::Pending->value)
        ->assertCanSeeTableRecords([$pendingOrder])
        ->assertCanNotSeeTableRecords([$deliveredOrder]);
});

test('can render the create order page', function () {
    $this->get(OrderResource::getUrl('create'))->assertSuccessful();
});

test('can create an order', function () {
    $customer = User::factory()->create();

    Livewire::test(CreateOrder::class)
        ->fillForm([
            'user_id' => $customer->id,
            'status' => OrderStatus::Pending->value,
            'total' => 150.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Order::class, [
        'user_id' => $customer->id,
        'status' => OrderStatus::Pending->value,
    ]);
});

test('can validate required fields when creating an order', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm([
            'user_id' => null,
            'status' => null,
            'total' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'user_id' => 'required',
            'status' => 'required',
            'total' => 'required',
        ]);
});

test('can render the edit order page', function () {
    $order = Order::factory()->create();

    $this->get(OrderResource::getUrl('edit', ['record' => $order]))->assertSuccessful();
});

test('can update an order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm([
            'status' => OrderStatus::Processing->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->refresh()->status)->toBe(OrderStatus::Processing);
});
