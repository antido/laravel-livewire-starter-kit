@props([
    'padding' => true,
])

<div {{ $attributes->class('rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900') }}>
    @isset($header)
        <div {{ $header->attributes->class('flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800') }}>
            {{ $header }}
        </div>
    @endisset

    <div @class(['p-5 sm:p-6' => $padding])>{{ $slot }}</div>

    @isset($footer)
        <div {{ $footer->attributes->class('flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/30') }}>
            {{ $footer }}
        </div>
    @endisset
</div>
