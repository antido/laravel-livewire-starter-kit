@props([
    'align' => 'right',
    'width' => 'w-56',
    // The accessible name of the trigger button.
    'label' => 'Open menu',
    // Extra classes for the menu panel, e.g. to pin it full-width on phones.
    'panelClass' => '',
])

{{--
    <x-ui.dropdown label="Account">
        <x-slot:trigger>…</x-slot:trigger>
        <a href="…" role="menuitem" class="…">Settings</a>
    </x-ui.dropdown>

    Items with role="menuitem" can be reached with the arrow keys; Esc closes the menu and returns focus.
--}}
<div
    x-data="{
        open: false,
        toggle() { this.open ? this.close() : this.show() },
        show() {
            this.open = true;
            this.$nextTick(() => this.items()[0]?.focus());
        },
        close(focusTrigger = true) {
            if (! this.open) return;
            this.open = false;
            if (focusTrigger) this.$refs.trigger.focus();
        },
        items() { return [...this.$refs.panel.querySelectorAll('[role=menuitem]:not([disabled])')] },
        move(step) {
            const items = this.items();
            if (! items.length) return;
            const index = items.indexOf(document.activeElement);
            items[(index + step + items.length) % items.length].focus();
        },
    }"
    x-on:keydown.escape.prevent.stop="close()"
    x-on:click.outside="close(false)"
    {{ $attributes->class('relative') }}
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="show()"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="menu"
        aria-label="{{ $label }}"
        class="flex items-center rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
    >
        {{ $trigger }}
    </button>

    <div
        x-ref="panel"
        x-show="open"
        x-cloak
        x-transition:enter="duration-100 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="duration-75 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.tab="close(false)"
        role="menu"
        @class([
            'absolute z-40 mt-2 max-w-[calc(100vw-2rem)] origin-top rounded-xl border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900',
            'right-0' => $align === 'right',
            'left-0' => $align === 'left',
            $width,
            $panelClass,
        ])
    >
        {{ $slot }}
    </div>
</div>
