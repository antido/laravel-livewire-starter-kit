@props([
    'href' => null,
    'icon' => null,
    'danger' => false,
])

@php
    $classes = implode(' ', [
        'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition focus:outline-none',
        $danger
            ? 'text-red-600 hover:bg-red-50 focus:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 dark:focus:bg-red-500/10'
            : 'text-zinc-700 hover:bg-zinc-100 focus:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4 text-zinc-400 dark:text-zinc-500" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" role="menuitem" {{ $attributes->except('type')->class($classes) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4 text-zinc-400 dark:text-zinc-500" />
        @endif
        {{ $slot }}
    </button>
@endif
