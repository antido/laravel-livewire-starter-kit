<div>
    <x-ui.page-header :title="'Edit '.$employee->full_name" :description="$employee->employee_number.' · '.$employee->position" />

    <form wire:submit="save" class="max-w-5xl">
        @include('livewire.employees.partials.form-fields')

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="secondary" :href="route('employees.show', $employee)">Cancel</x-ui.button>
            <x-ui.button type="submit" loading="save">Save changes</x-ui.button>
        </div>
    </form>
</div>
