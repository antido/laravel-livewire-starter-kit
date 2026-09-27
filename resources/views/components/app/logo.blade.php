@props(['href' => url('/')])

<a href="{{ $href }}" {{ $attributes->class('inline-flex min-w-0 items-center gap-2.5 rounded-lg font-semibold tracking-tight text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent-600 dark:text-zinc-50') }}>
    <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-accent-700 text-white dark:bg-accent-500 dark:text-zinc-950" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M4 7l8-4 8 4-8 4-8-4Z"/><path d="m4 12 8 4 8-4"/><path d="m4 17 8 4 8-4"/></svg>
    </span>
    <span class="truncate">{{ config('app.name') }}</span>
</a>
