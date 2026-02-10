<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryUsers implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search users by name or email. Returns only safe fields: id, name, email, and registration date. Never exposes passwords or sensitive data.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = User::query()->select(['id', 'name', 'email', 'created_at']);

        if ($request['search'] ?? null) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request['has_orders'] ?? false) {
            $query->whereHas('orders');
        }

        $users = $query->orderBy('name')->limit(50)->get();

        if ($users->isEmpty()) {
            return 'No users found matching the criteria.';
        }

        return $users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'registered_at' => $user->created_at->toDateTimeString(),
        ])->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term to filter users by name or email'),
            'has_orders' => $schema->boolean()->description('If true, only return users who have placed at least one order'),
        ];
    }
}
