@props([
    'title',
    'description' => null,
])

<x-ui.card>
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    {{ $slot }}

    @isset($footer)
        <div class="mt-6 border-t border-zinc-200 pt-4 text-center text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
            {{ $footer }}
        </div>
    @endisset
</x-ui.card>
