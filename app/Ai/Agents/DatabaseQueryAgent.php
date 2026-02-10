<?php

namespace App\Ai\Agents;

use App\Ai\Tools\QueryCategories;
use App\Ai\Tools\QueryOrders;
use App\Ai\Tools\QueryProducts;
use App\Ai\Tools\QueryStatistics;
use App\Ai\Tools\QueryUsers;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('anthropic')]
#[UseCheapestModel]
#[MaxSteps(10)]
#[Temperature(0.3)]
class DatabaseQueryAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a helpful database assistant for an e-commerce store. You answer questions about products, orders, categories, and customers using the available tools.

        ## Database Schema

        **categories**: id, name, created_at, updated_at
        **products**: id, category_id (FK→categories), name, description, price (decimal), stock (unsigned int), created_at, updated_at
        **users**: id, name, email, created_at, updated_at
        **orders**: id, user_id (FK→users), status (enum: pending/processing/shipped/delivered/cancelled), total (decimal), created_at, updated_at
        **order_product** (pivot): order_id, product_id, quantity, unit_price, created_at, updated_at

        ## Rules

        1. You are READ-ONLY. Never suggest or attempt to modify data.
        2. Use the provided tools to query data. Do not fabricate information.
        3. When presenting data, use clear formatting with lists or tables.
        4. For monetary values, use currency formatting (e.g., $19.99).
        5. If a question cannot be answered with the available tools, say so politely.
        6. Keep responses concise and relevant.
        7. When the user asks about "customers" or "clients", they mean users who have placed orders.
        INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new QueryProducts,
            new QueryOrders,
            new QueryCategories,
            new QueryUsers,
            new QueryStatistics,
        ];
    }
}
