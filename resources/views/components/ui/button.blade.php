@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'disabled' => false,
    // The Livewire action to show a spinner for. Defaults to the wire:click action; submit buttons spin for any request.
    'loading' => null,
])

@php
    $variants = [
        'primary' => 'bg-accent-700 text-white shadow-sm hover:bg-accent-800 focus-visible:outline-accent-600 dark:bg-accent-500 dark:text-zinc-950 dark:hover:bg-accent-400',
        'secondary' => 'bg-white text-zinc-700 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 focus-visible:outline-accent-600 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-800',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:outline-red-600 dark:bg-red-500 dark:hover:bg-red-400',
    ];

    $sizes = [
        'sm' => 'h-8 gap-1.5 rounded-md px-3 text-xs',
        'md' => 'h-10 gap-2 rounded-lg px-4 text-sm',
        'lg' => 'h-11 gap-2 rounded-lg px-5 text-base',
    ];

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center font-medium whitespace-nowrap transition',
        'focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);

    $target = $loading ?? $attributes->get('wire:click');
    $showsLoading = $target !== null || $type === 'submit';
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        @if ($showsLoading) wire:loading.attr="disabled" @if ($target) wire:target="{{ $target }}" @endif @endif
        {{ $attributes->class($classes) }}
    >
        @if ($showsLoading)
            <span wire:loading.inline-flex @if ($target) wire:target="{{ $target }}" @endif class="hidden">
                <x-ui.spinner class="size-4" />
            </span>
        @endif
        @if ($icon)
            <span class="inline-flex" @if ($showsLoading) wire:loading.remove @if ($target) wire:target="{{ $target }}" @endif @endif>
                <x-ui.icon :name="$icon" class="size-4" />
            </span>
        @endif
        {{ $slot }}
    </button>
@endif
