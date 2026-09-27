@props([
    'name' => '',
    'size' => 'md',
])

@php
    $words = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    $sizes = ['sm' => 'size-7 text-[11px]', 'md' => 'size-9 text-xs', 'lg' => 'size-12 text-sm'];
@endphp

<span {{ $attributes->class(['inline-grid shrink-0 place-items-center rounded-full bg-accent-100 font-semibold text-accent-800 select-none dark:bg-accent-500/15 dark:text-accent-300', $sizes[$size] ?? $sizes['md']]) }} aria-hidden="true">
    {{ $initials !== '' ? $initials : '?' }}
</span>
