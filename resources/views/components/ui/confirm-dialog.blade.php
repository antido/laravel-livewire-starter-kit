{{--
    One global confirmation dialog, mounted once per layout and built on the modal. Open it from anywhere:

    <x-ui.button x-on:click="$dispatch('confirm', {
        title: 'Delete role?',
        message: 'This cannot be undone.',
        confirmLabel: 'Delete',
        danger: true,
        action: () => $wire.delete({{ $role->id }}),
    })">Delete</x-ui.button>

    It is only a safeguard against mistakes: the server authorizes every action on its own.
--}}
<div
    x-data="{
        title: '',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        danger: false,
        action: null,
        ask(detail) {
            this.title = detail.title ?? 'Are you sure?';
            this.message = detail.message ?? '';
            this.confirmLabel = detail.confirmLabel ?? 'Confirm';
            this.cancelLabel = detail.cancelLabel ?? 'Cancel';
            this.danger = detail.danger ?? false;
            this.action = detail.action ?? null;
            $dispatch('open-modal', 'confirm-dialog');
        },
        run() {
            $dispatch('close-modal', 'confirm-dialog');
            if (typeof this.action === 'function') this.action();
        },
    }"
    x-on:confirm.window="ask($event.detail)"
>
    <x-ui.modal name="confirm-dialog" max-width="md" role="alertdialog" labelledby="confirm-dialog-title" describedby="confirm-dialog-message">
        <div class="flex gap-4">
            <span class="grid size-10 shrink-0 place-items-center rounded-full" x-bind:class="danger ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-400'">
                <x-ui.icon name="alert" class="size-5" />
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100" x-text="title"></h2>
                <p id="confirm-dialog-message" class="mt-1 text-sm text-zinc-600 dark:text-zinc-400" x-text="message"></p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="secondary" x-on:click="open = false" x-text="cancelLabel">Cancel</x-ui.button>
            <button
                type="button"
                x-on:click="run()"
                x-text="confirmLabel"
                data-test="confirm-dialog-confirm"
                class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-medium shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2"
                x-bind:class="danger ? 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600 dark:bg-red-500 dark:hover:bg-red-400' : 'bg-accent-700 text-white hover:bg-accent-800 focus-visible:outline-accent-600 dark:bg-accent-500 dark:text-zinc-950 dark:hover:bg-accent-400'"
            ></button>
        </div>
    </x-ui.modal>
</div>
