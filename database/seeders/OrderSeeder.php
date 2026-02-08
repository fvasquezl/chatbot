<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        User::all()->each(function (User $user) use ($products) {
            Order::factory(2)->create(['user_id' => $user->id])->each(function (Order $order) use ($products) {
                $selectedProducts = $products->random(rand(1, 3));
                $total = 0;

                $selectedProducts->each(function (Product $product) use ($order, &$total) {
                    $quantity = rand(1, 5);
                    $unitPrice = $product->price;
                    $total += $quantity * $unitPrice;

                    $order->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                });

                $order->update(['total' => $total]);
            });
        });
    }
}
