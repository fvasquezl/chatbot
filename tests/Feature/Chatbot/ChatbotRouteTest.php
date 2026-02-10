<?php

use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('chatbot'))
        ->assertRedirect(route('login'));
});

it('allows authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chatbot'))
        ->assertOk();
});

it('allows accessing chatbot with conversation id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chatbot.conversation', ['conversation' => 'test-id']))
        ->assertOk();
});
