<div>
    <x-ui.page-header title="Add role" description="Name the role and choose what it is allowed to do." />

    <form wire:submit="save" class="max-w-5xl">
        <x-ui.card>
            @include('livewire.admin.roles.partials.form-fields')

            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.roles.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" loading="save">Create role</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>
