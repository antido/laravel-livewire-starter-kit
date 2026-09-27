@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $styles = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
        'danger' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200',
    ];
    $icons = ['info' => 'info', 'success' => 'check-circle', 'warning' => 'alert', 'danger' => 'x-circle'];
    $variant = array_key_exists($variant, $styles) ? $variant : 'info';
@endphp

<div
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" x-transition.opacity @endif
    role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', $styles[$variant]]) }}
>
    <x-ui.icon :name="$icons[$variant]" class="mt-0.5 size-4" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" x-on:click="shown = false" class="-m-1 rounded p-1 opacity-70 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-current" aria-label="Dismiss">
            <x-ui.icon name="x" class="size-4" />
        </button>
    @endif
</div>
