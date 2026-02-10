<?php

use App\Ai\Tools\QueryUsers;
use App\Models\Order;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tool = new QueryUsers;
});

it('returns all users with safe fields only', function () {
    User::factory(3)->create();

    $result = json_decode($this->tool->handle(new Request([])), true);

    expect($result)->toHaveCount(3)
        ->and($result[0])->toHaveKeys(['id', 'name', 'email', 'registered_at'])
        ->and($result[0])->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret']);
});

it('searches users by name', function () {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    $result = json_decode($this->tool->handle(new Request(['search' => 'Alice'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Alice Smith');
});

it('searches users by email', function () {
    User::factory()->create(['email' => 'alice@example.com']);
    User::factory()->create(['email' => 'bob@example.com']);

    $result = json_decode($this->tool->handle(new Request(['search' => 'alice@'])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['email'])->toBe('alice@example.com');
});

it('filters users who have orders', function () {
    $userWithOrders = User::factory()->create();
    User::factory()->create();
    Order::factory()->create(['user_id' => $userWithOrders->id]);

    $result = json_decode($this->tool->handle(new Request(['has_orders' => true])), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe($userWithOrders->id);
});

it('returns a message when no users match', function () {
    $result = $this->tool->handle(new Request(['search' => 'nonexistent']));

    expect($result)->toBe('No users found matching the criteria.');
});

it('never exposes password field', function () {
    User::factory()->create();

    $result = $this->tool->handle(new Request([]));

    expect($result)->not->toContain('password');
});
