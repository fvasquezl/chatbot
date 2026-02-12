<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main :class="($fullHeight ?? false) ? 'p-0! overflow-hidden' : ''">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
