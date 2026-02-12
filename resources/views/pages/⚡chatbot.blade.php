<?php

use App\Ai\Agents\DatabaseQueryAgent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Chatbot', 'fullHeight' => true])] class extends Component {
    public string $message = '';

    #[Locked]
    public ?string $conversationId = null;

    #[Locked]
    public bool $isProcessing = false;

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public function mount(?string $conversation = null): void
    {
        if ($conversation) {
            $this->selectConversation($conversation);
        }
    }

    public function sendMessage(): void
    {
        $this->validate(['message' => 'required|string|max:1000']);

        $this->messages[] = ['role' => 'user', 'content' => $this->message];
        $this->message = '';
        $this->isProcessing = true;

        $this->js('$wire.processMessage()');
    }

    public function processMessage(): void
    {
        $user = Auth::user();
        $userMessage = end($this->messages)['content'];

        $agent = new DatabaseQueryAgent;

        if ($this->conversationId) {
            $agent = $agent->continue($this->conversationId, as: $user);
        } else {
            $agent = $agent->forUser($user);
        }

        $stream = $agent->stream($userMessage);

        foreach ($stream as $event) {
            $this->stream(to: 'response', content: $event->text ?? '');
        }

        $this->conversationId = $stream->conversationId;
        $this->messages[] = ['role' => 'assistant', 'content' => $stream->text];
        $this->isProcessing = false;
    }

    public function selectConversation(string $id): void
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $conversation) {
            return;
        }

        $this->conversationId = $conversation->id;

        $this->messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
    }

    public function startNewConversation(): void
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->message = '';
        $this->isProcessing = false;
    }

    #[Computed]
    public function conversations(): array
    {
        return DB::table('agent_conversations')
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'updated_at' => $c->updated_at])
            ->all();
    }

    public function askSuggestion(string $question): void
    {
        $this->message = $question;
        $this->sendMessage();
    }
}; ?>

<div class="flex h-full overflow-hidden">
    {{-- Main chat area --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Mobile header: conversation dropdown --}}
        <div class="flex items-center gap-2 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 lg:hidden">
            <flux:dropdown>
                <flux:button variant="subtle" icon="chat-bubble-left-right" size="sm">
                    {{ __('Conversations') }}
                </flux:button>

                <flux:menu>
                    <flux:menu.item wire:click="startNewConversation" icon="plus">
                        {{ __('New conversation') }}
                    </flux:menu.item>

                    @if (count($this->conversations) > 0)
                        <flux:menu.separator />
                    @endif

                    @foreach ($this->conversations as $conv)
                        <flux:menu.item wire:key="mobile-conv-{{ $conv['id'] }}" wire:click="selectConversation('{{ $conv['id'] }}')">
                            {{ $conv['title'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            <flux:spacer />

            <flux:button variant="subtle" icon="plus" size="sm" wire:click="startNewConversation" />
        </div>

        {{-- Messages --}}
        <div
            class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 md:p-6"
            x-data
            x-effect="$el.scrollTop = $el.scrollHeight"
        >
            @if (count($messages) === 0 && ! $isProcessing)
                {{-- Empty state --}}
                <div class="flex h-full flex-col items-center justify-center gap-6">
                    <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.chat-bubble-left-right class="size-8 text-zinc-400 dark:text-zinc-500" />
                    </div>

                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Store Assistant') }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Ask me anything about products, orders, categories, or customers.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ([
                            __('What are the top selling products?'),
                            __('How many orders are pending?'),
                            __('Which products are low on stock?'),
                            __('Show me revenue by order status'),
                        ] as $suggestion)
                            <button
                                wire:click="askSuggestion('{{ $suggestion }}')"
                                class="rounded-lg border border-zinc-200 px-4 py-2 text-left text-sm text-zinc-600 transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800"
                            >
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                @foreach ($messages as $index => $msg)
                    <div wire:key="msg-{{ $index }}" @class([
                        'flex',
                        'justify-end' => $msg['role'] === 'user',
                        'justify-start' => $msg['role'] === 'assistant',
                    ])>
                        <div @class([
                            'max-w-[80%] rounded-2xl px-4 py-3 text-sm',
                            'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $msg['role'] === 'user',
                            'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' => $msg['role'] === 'assistant',
                        ])>
                            @if ($msg['role'] === 'assistant')
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! str($msg['content'])->markdown() !!}
                                </div>
                            @else
                                {{ $msg['content'] }}
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Streaming response --}}
                @if ($isProcessing)
                    <div class="flex justify-start">
                        <div class="max-w-[80%] rounded-2xl bg-zinc-100 px-4 py-3 text-sm text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
                            <div class="prose prose-sm dark:prose-invert max-w-none" wire:stream="response">
                                <span class="inline-block animate-pulse">{{ __('Thinking...') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Input --}}
        <div class="shrink-0 border-t border-zinc-200 p-4 dark:border-zinc-700">
            <form wire:submit="sendMessage" class="flex items-center gap-2">
                <flux:input
                    wire:model="message"
                    placeholder="{{ __('Ask about products, orders, categories...') }}"
                    :disabled="$isProcessing"
                    autofocus
                    class="flex-1"
                />

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="paper-airplane"
                    :disabled="$isProcessing"
                />
            </form>
        </div>
    </div>

    {{-- Right sidebar: conversation list (desktop only) --}}
    <div class="hidden w-72 shrink-0 flex-col border-s border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 lg:flex">
        <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Conversations') }}</h2>
            <flux:button variant="subtle" icon="plus" size="sm" wire:click="startNewConversation" />
        </div>

        <div class="min-h-0 flex-1 space-y-1 overflow-y-auto p-2">
            @forelse ($this->conversations as $conv)
                <button
                    wire:key="conv-{{ $conv['id'] }}"
                    wire:click="selectConversation('{{ $conv['id'] }}')"
                    @class([
                        'w-full rounded-lg px-3 py-2 text-left text-sm transition-colors',
                        'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' => $conversationId === $conv['id'],
                        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => $conversationId !== $conv['id'],
                    ])
                >
                    <span class="block truncate">{{ $conv['title'] }}</span>
                </button>
            @empty
                <p class="px-3 py-2 text-sm text-zinc-400 dark:text-zinc-500">{{ __('No conversations yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
