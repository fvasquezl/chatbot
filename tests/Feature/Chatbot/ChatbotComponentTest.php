<?php

use App\Ai\Agents\DatabaseQueryAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('renders the chatbot page', function () {
    $this->actingAs($this->user)
        ->get(route('chatbot'))
        ->assertOk()
        ->assertSee('Store Assistant');
});

it('shows suggestion buttons on empty state', function () {
    $this->actingAs($this->user)
        ->get(route('chatbot'))
        ->assertSee('What are the top selling products?')
        ->assertSee('How many orders are pending?');
});

it('validates message is required', function () {
    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->set('message', '')
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'required']);
});

it('validates message max length', function () {
    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->set('message', str_repeat('a', 1001))
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'max']);
});

it('adds user message to messages array on send', function () {
    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->set('message', 'Hello')
        ->call('sendMessage')
        ->assertSet('messages', [['role' => 'user', 'content' => 'Hello']])
        ->assertSet('message', '')
        ->assertSet('isProcessing', true);
});

it('can start a new conversation', function () {
    $conversationId = (string) Str::uuid();

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $this->user->id,
        'title' => 'Test Conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $this->user->id,
        'agent' => DatabaseQueryAgent::class,
        'role' => 'user',
        'content' => 'Hello',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
        'meta' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->call('selectConversation', $conversationId)
        ->assertSet('conversationId', $conversationId)
        ->call('startNewConversation')
        ->assertSet('conversationId', null)
        ->assertSet('messages', [])
        ->assertSet('message', '')
        ->assertSet('isProcessing', false);
});

it('can select an existing conversation', function () {
    $conversationId = (string) Str::uuid();

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $this->user->id,
        'title' => 'Test Conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $this->user->id,
        'agent' => DatabaseQueryAgent::class,
        'role' => 'user',
        'content' => 'Hello',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
        'meta' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->call('selectConversation', $conversationId)
        ->assertSet('conversationId', $conversationId)
        ->assertCount('messages', 1);
});

it('cannot select another users conversation', function () {
    $otherUser = User::factory()->create();
    $conversationId = (string) Str::uuid();

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $otherUser->id,
        'title' => 'Other User Conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->call('selectConversation', $conversationId)
        ->assertSet('conversationId', null)
        ->assertSet('messages', []);
});

it('lists conversations for the current user', function () {
    DB::table('agent_conversations')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'title' => 'My Conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::chatbot')
        ->assertSee('My Conversation');
});
