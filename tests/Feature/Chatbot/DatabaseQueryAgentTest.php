<?php

use App\Ai\Agents\DatabaseQueryAgent;
use App\Ai\Tools\QueryCategories;
use App\Ai\Tools\QueryOrders;
use App\Ai\Tools\QueryProducts;
use App\Ai\Tools\QueryStatistics;
use App\Ai\Tools\QueryUsers;

it('can be instantiated', function () {
    $agent = new DatabaseQueryAgent;

    expect($agent)->toBeInstanceOf(DatabaseQueryAgent::class);
});

it('has instructions about the database schema', function () {
    $agent = new DatabaseQueryAgent;

    $instructions = $agent->instructions();

    expect($instructions)->toContain('READ-ONLY')
        ->and($instructions)->toContain('categories')
        ->and($instructions)->toContain('products')
        ->and($instructions)->toContain('orders')
        ->and($instructions)->toContain('users');
});

it('registers all five tools', function () {
    $agent = new DatabaseQueryAgent;
    $tools = iterator_to_array($agent->tools());

    expect($tools)->toHaveCount(5)
        ->and($tools[0])->toBeInstanceOf(QueryProducts::class)
        ->and($tools[1])->toBeInstanceOf(QueryOrders::class)
        ->and($tools[2])->toBeInstanceOf(QueryCategories::class)
        ->and($tools[3])->toBeInstanceOf(QueryUsers::class)
        ->and($tools[4])->toBeInstanceOf(QueryStatistics::class);
});

it('can be faked for testing', function () {
    DatabaseQueryAgent::fake(['Hello! How can I help you?']);

    $response = (new DatabaseQueryAgent)->prompt('Hi');

    expect((string) $response)->toBe('Hello! How can I help you?');
});

it('records prompts when faked', function () {
    DatabaseQueryAgent::fake(['Response']);

    (new DatabaseQueryAgent)->prompt('What are the top products?');

    DatabaseQueryAgent::assertPrompted('What are the top products?');
});
