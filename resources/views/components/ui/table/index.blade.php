{{--
    A responsive table: it scrolls sideways inside its card on narrow screens.

    <x-ui.table>
        <x-slot:head>
            <x-ui.table.th sortable :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">Name</x-ui.table.th>
            <x-ui.table.th>Email</x-ui.table.th>
        </x-slot:head>
        <x-ui.table.tr>
            <x-ui.table.td>…</x-ui.table.td>
        </x-ui.table.tr>
    </x-ui.table>
--}}
<div {{ $attributes->class('overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900') }}>
    {{-- "relative" keeps absolutely positioned content (such as sr-only text) inside the scroll area. --}}
    <div class="relative overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            @isset($head)
                <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-zinc-100 text-zinc-700 dark:divide-zinc-800 dark:text-zinc-300">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">{{ $footer }}</div>
    @endisset
</div>
