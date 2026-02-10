<?php

namespace App\Ai\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryOrders implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search and filter orders by status, user, date range, and total amount. Returns order details including user, status, total, and products.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = Order::query()->with(['user:id,name,email', 'products:id,name']);

        if ($request['status'] ?? null) {
            $status = OrderStatus::tryFrom($request['status']);
            if ($status) {
                $query->where('status', $status);
            }
        }

        if ($request['user_id'] ?? null) {
            $query->where('user_id', $request['user_id']);
        }

        if ($request['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $request['date_from']);
        }

        if ($request['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $request['date_to']);
        }

        if ($request['min_total'] ?? null) {
            $query->where('total', '>=', $request['min_total']);
        }

        if ($request['max_total'] ?? null) {
            $query->where('total', '<=', $request['max_total']);
        }

        $orders = $query->orderByDesc('created_at')->limit(50)->get();

        if ($orders->isEmpty()) {
            return 'No orders found matching the criteria.';
        }

        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'user' => $order->user->name,
            'user_email' => $order->user->email,
            'status' => $order->status->value,
            'total' => $order->total,
            'products' => $order->products->map(fn ($p) => [
                'name' => $p->name,
                'quantity' => $p->pivot->quantity,
                'unit_price' => $p->pivot->unit_price,
            ])->all(),
            'created_at' => $order->created_at->toDateTimeString(),
        ])->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by order status: pending, processing, shipped, delivered, cancelled'),
            'user_id' => $schema->integer()->description('Filter by user ID'),
            'date_from' => $schema->string()->description('Start date filter (YYYY-MM-DD)'),
            'date_to' => $schema->string()->description('End date filter (YYYY-MM-DD)'),
            'min_total' => $schema->number()->description('Minimum order total'),
            'max_total' => $schema->number()->description('Maximum order total'),
        ];
    }
}
