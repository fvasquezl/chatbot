<?php

namespace App\Ai\Tools;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryStatistics implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get aggregate statistics about the store: total revenue, order counts by status, top selling products, top customers by spending, and low stock products.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $type = $request['type'] ?? 'overview';

        return match ($type) {
            'overview' => $this->overview(),
            'top_products' => $this->topProducts(),
            'top_customers' => $this->topCustomers(),
            'low_stock' => $this->lowStock(),
            'revenue_by_status' => $this->revenueByStatus(),
            default => 'Unknown statistic type. Available types: overview, top_products, top_customers, low_stock, revenue_by_status',
        };
    }

    private function overview(): string
    {
        return json_encode([
            'total_revenue' => Order::query()->sum('total'),
            'total_orders' => Order::query()->count(),
            'total_products' => Product::query()->count(),
            'total_customers' => User::query()->whereHas('orders')->count(),
            'average_order_value' => round((float) Order::query()->avg('total'), 2),
        ]);
    }

    private function topProducts(): string
    {
        $products = Product::query()
            ->select(['products.id', 'products.name', 'products.price'])
            ->join('order_product', 'products.id', '=', 'order_product.product_id')
            ->selectRaw('SUM(order_product.quantity) as total_sold')
            ->selectRaw('SUM(order_product.quantity * order_product.unit_price) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            return 'No product sales data available.';
        }

        return $products->toJson();
    }

    private function topCustomers(): string
    {
        $customers = User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->whereHas('orders')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->orderByDesc('orders_sum_total')
            ->limit(10)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'orders_count' => $user->orders_count,
                'total_spent' => $user->orders_sum_total,
            ]);

        if ($customers->isEmpty()) {
            return 'No customer data available.';
        }

        return $customers->toJson();
    }

    private function lowStock(): string
    {
        $products = Product::query()
            ->with('category:id,name')
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->limit(20)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'price' => $product->price,
                'category' => $product->category->name,
            ]);

        if ($products->isEmpty()) {
            return 'No low stock products found.';
        }

        return $products->toJson();
    }

    private function revenueByStatus(): string
    {
        $data = Order::query()
            ->select('status')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total) as total_revenue')
            ->groupBy('status')
            ->get()
            ->map(fn (Order $order) => [
                'status' => $order->status->value,
                'order_count' => $order->order_count,
                'total_revenue' => $order->total_revenue,
            ]);

        return $data->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->description('Type of statistic: overview, top_products, top_customers, low_stock, revenue_by_status'),
        ];
    }
}
