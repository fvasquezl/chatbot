<?php

namespace App\Ai\Tools;

use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryCategories implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List and search categories with their product counts. Use this to find categories by name or list all available categories.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = Category::query()->withCount('products');

        if ($request['search'] ?? null) {
            $query->where('name', 'like', "%{$request['search']}%");
        }

        $categories = $query->orderBy('name')->limit(50)->get();

        if ($categories->isEmpty()) {
            return 'No categories found matching the criteria.';
        }

        return $categories->map(fn (Category $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'products_count' => $category->products_count,
        ])->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term to filter categories by name'),
        ];
    }
}
