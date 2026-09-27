<div>
    <x-ui.page-header :title="'Reset password for '.$user->name" :description="$user->email" />

    <div class="grid max-w-3xl grid-cols-1 gap-6">
        <form wire:submit="save">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Set a new password</h2>
                </x-slot:header>

                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">Share it with the user through a safe channel. It is never shown again.</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input label="New password" type="password" wire:model="password" autocomplete="new-password" hint="At least 8 characters." required />
                    <x-ui.input label="Confirm new password" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" :href="route('admin.users.show', $user)">Cancel</x-ui.button>
                    <x-ui.button type="submit" loading="save">Change password</x-ui.button>
                </x-slot:footer>
            </x-ui.card>
        </form>

        <x-ui.card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Or email a reset link</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">The user chooses their own password from a link sent to {{ $user->email }}.</p>
                </div>
                <x-ui.button variant="secondary" wire:click="sendLink">Send reset link</x-ui.button>
            </div>
        </x-ui.card>
    </div>
</div>
