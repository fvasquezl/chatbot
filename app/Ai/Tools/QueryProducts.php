<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryProducts implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search and filter products by name, category, price range, and stock level. Returns product details including name, description, price, stock, and category.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = Product::query()->with('category');

        if ($request['search'] ?? null) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request['category_id'] ?? null) {
            $query->where('category_id', $request['category_id']);
        }

        if ($request['min_price'] ?? null) {
            $query->where('price', '>=', $request['min_price']);
        }

        if ($request['max_price'] ?? null) {
            $query->where('price', '<=', $request['max_price']);
        }

        if ($request['low_stock'] ?? false) {
            $query->where('stock', '<=', 10);
        }

        $products = $query->orderBy('name')->limit(50)->get();

        if ($products->isEmpty()) {
            return 'No products found matching the criteria.';
        }

        return $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'category' => $product->category->name,
        ])->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term to filter products by name or description'),
            'category_id' => $schema->integer()->description('Filter by category ID'),
            'min_price' => $schema->number()->description('Minimum price filter'),
            'max_price' => $schema->number()->description('Maximum price filter'),
            'low_stock' => $schema->boolean()->description('If true, only return products with stock <= 10'),
        ];
    }
}
