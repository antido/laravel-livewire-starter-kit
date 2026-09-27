@props([
    // The accessible label (visually hidden).
    'label' => 'Search',
])

@php($id = $attributes->get('id') ?? 'search-'.uniqid())

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    {{-- The icon is positioned against the input itself, so a taller grid row cannot move it. --}}
    <div class="relative">
        <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
        <input
            id="{{ $id }}"
            type="search"
            maxlength="100"
            autocomplete="off"
            {{ $attributes->except(['class', 'id']) }}
            class="block h-10 w-full rounded-lg border border-zinc-300 bg-white pr-3 pl-9 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-accent-500 focus:ring-4 focus:ring-accent-500/15 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500"
        >
    </div>
</div>
