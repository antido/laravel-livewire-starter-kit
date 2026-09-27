<div>
    <x-ui.page-header :title="'Edit '.$role->name" :description="$role->isSystem() ? 'A system role: its name is fixed, its permissions can change.' : 'Rename the role or change what it is allowed to do.'" />

    <form wire:submit="save" class="max-w-5xl">
        <x-ui.card>
            @include('livewire.admin.roles.partials.form-fields')

            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.roles.index')">Cancel</x-ui.button>
                @unless ($role->isSuperAdmin())
                    <x-ui.button type="submit" loading="save">Save changes</x-ui.button>
                @endunless
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>
