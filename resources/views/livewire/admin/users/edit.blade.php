<div>
    <x-ui.page-header :title="'Edit '.$user->name" description="Update the account details, roles and status." />

    <form wire:submit="save" class="max-w-3xl">
        <x-ui.card>
            @include('livewire.admin.users.partials.form-fields')

            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.users.show', $user)">Cancel</x-ui.button>
                <x-ui.button type="submit" loading="save">Save changes</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>
