{{--
    The toast container, mounted once per layout. It shows the toasts in the global Alpine store
    (resources/js/app.js). Trigger one from Livewire with $this->dispatch('toast', type: 'success', message: '...'),
    or flash "success", "error", "warning" or "info" to the session before a redirect.
--}}
@php
    $flashed = collect(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'])
        ->map(fn (string $type, string $key): ?array => is_string(session($key)) ? ['type' => $type, 'message' => session($key)] : null)
        ->filter()
        ->values();
@endphp

<div
    x-data
    x-init="@js($flashed).forEach((toast) => $store.toasts.add(toast))"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in $store.toasts.items" x-bind:key="toast.id">
        <div
            x-transition:enter="duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800 shadow-lg dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            x-bind:role="toast.type === 'danger' ? 'alert' : 'status'"
            data-test="toast"
        >
            <span
                class="mt-1.5 size-2 shrink-0 rounded-full"
                x-bind:class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-red-500': toast.type === 'danger' || toast.type === 'error',
                    'bg-amber-500': toast.type === 'warning',
                    'bg-sky-500': toast.type === 'info',
                }"
            ></span>
            <p class="flex-1" x-text="toast.message"></p>
            <button type="button" x-on:click="$store.toasts.remove(toast.id)" class="-m-1 rounded p-1 text-zinc-400 hover:text-zinc-700 focus-visible:outline-2 focus-visible:outline-accent-600 dark:hover:text-zinc-200" aria-label="Dismiss notification">
                <x-ui.icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
