@props([
    // Signed-in users: also save the choice to the account (the button must sit inside the ThemeToggle Livewire component).
    'persist' => false,
])

<button
    type="button"
    x-data
    x-on:click="$store.theme.toggle(); {{ $persist ? '$wire.save($store.theme.preference)' : '' }}"
    x-bind:aria-label="$store.theme.resolved === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    x-bind:title="$store.theme.resolved === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    aria-label="Switch theme"
    data-test="theme-toggle"
    {{ $attributes->class('grid size-9 place-items-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100') }}
>
    {{-- Sun in dark mode (switch to light), moon in light mode (switch to dark). --}}
    <x-ui.icon name="sun" class="hidden size-5 dark:block" />
    <x-ui.icon name="moon" class="size-5 dark:hidden" />
</button>
