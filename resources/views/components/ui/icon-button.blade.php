@props([
    'icon',
    // The accessible name; also shown as a tooltip.
    'label',
    'href' => null,
    'danger' => false,
])

@php
    $classes = implode(' ', [
        'inline-grid size-8 shrink-0 place-items-center rounded-lg transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600 disabled:pointer-events-none disabled:opacity-50',
        $danger
            ? 'text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-500/10 dark:hover:text-red-400'
            : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-ui.icon :name="$icon" class="size-4" />
    </a>
@else
    <button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-ui.icon :name="$icon" class="size-4" />
    </button>
@endif
