@props([
    // Open or close it by name: $dispatch('open-modal', 'edit-role') / $dispatch('close-modal', 'edit-role').
    // Or bind it to a boolean Livewire property with wire:model="showModal".
    'name' => null,
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
    // "alertdialog" for confirmations; label it with the ids of your own heading and message.
    'role' => 'dialog',
    'labelledby' => null,
    'describedby' => null,
])

@php
    $widths = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl', '3xl' => 'sm:max-w-3xl'];
    $model = $attributes->wire('model')->value();
    $key = $name ?? $model ?? 'modal';
    $titleId = 'modal-title-'.\Illuminate\Support\Str::slug($key);
@endphp

{{--
    "open === true" is compared on purpose: while a page is being torn down an entangle update can
    re-run these effects after the scope is gone, and a bare "open" would resolve to window.open.
--}}
<div
    @if ($model)
        x-data="{ open: $wire.entangle('{{ $model }}') }"
    @else
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
        x-on:close-modal.window="if ($event.detail === @js($name)) open = false"
    @endif
    x-show="open === true"
    x-on:keydown.escape.window="open = false"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="{{ $role }}"
    aria-modal="true"
    @if ($labelledby || $title) aria-labelledby="{{ $labelledby ?? $titleId }}" @endif
    @if ($describedby) aria-describedby="{{ $describedby }}" @endif
>
    <div x-show="open === true" x-transition.opacity class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm dark:bg-zinc-950/70" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div
            x-show="open === true"
            x-trap.noscroll="open === true"
            x-transition:enter="duration-200 ease-out"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            {{ $attributes->whereDoesntStartWith('wire:model')->class(['relative w-full rounded-xl bg-white shadow-xl ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800', $widths[$maxWidth] ?? $widths['lg']]) }}
        >
            @if ($title)
                <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <div>
                        <h2 id="{{ $titleId }}" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
                        @if ($description)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                        @endif
                    </div>
                    <button type="button" x-on:click="open = false" class="rounded-md p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 focus-visible:outline-2 focus-visible:outline-accent-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300" aria-label="Close">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </div>
            @endif

            <div class="px-6 py-5">{{ $slot }}</div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 bg-zinc-50/60 px-6 py-4 sm:flex-row sm:justify-end dark:border-zinc-800 dark:bg-zinc-800/30">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
