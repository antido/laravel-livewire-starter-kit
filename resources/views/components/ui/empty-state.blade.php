@props([
    'icon' => 'inbox',
    'title',
    'message' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-12 text-center') }}>
    <span class="grid size-12 place-items-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <p class="mt-4 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
