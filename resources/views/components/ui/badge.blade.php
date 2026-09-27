@props(['variant' => 'neutral'])

@php
    $styles = [
        'neutral' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700',
        'accent' => 'bg-accent-50 text-accent-800 ring-accent-200 dark:bg-accent-500/10 dark:text-accent-300 dark:ring-accent-500/30',
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30',
        'danger' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset', $styles[$variant] ?? $styles['neutral']]) }}>
    {{ $slot }}
</span>
