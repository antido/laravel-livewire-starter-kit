<div>
    <x-ui.page-header title="Add employee" description="The employee number is assigned automatically." />

    <form wire:submit="save" class="max-w-5xl">
        @include('livewire.employees.partials.form-fields')

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="secondary" :href="route('employees.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" loading="save">Create employee</x-ui.button>
        </div>
    </form>
</div>
