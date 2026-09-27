<div>
    <x-ui.page-header title="Add user" description="Create an account that can sign in right away." />

    <form wire:submit="save" class="max-w-3xl">
        <x-ui.card>
            @include('livewire.admin.users.partials.form-fields')

            <div class="mt-6 grid grid-cols-1 gap-4 border-t border-zinc-200 pt-6 sm:grid-cols-2 dark:border-zinc-800">
                <x-ui.input label="Password" type="password" wire:model="password" autocomplete="new-password" hint="At least 8 characters." required />
                <x-ui.input label="Confirm password" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.users.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" loading="save">Create user</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>
