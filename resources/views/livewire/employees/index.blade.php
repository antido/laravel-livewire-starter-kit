<div>
    <x-ui.page-header title="Employees" description="Staff records, and the login accounts linked to them.">
        @can('create', \App\Models\Employee::class)
            <x-slot:actions>
                <x-ui.button :href="route('employees.create')" icon="plus">Add employee</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="mb-4 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_12rem_12rem]">
            <x-ui.search-input wire:model.live.debounce.400ms="search" id="employees-search" label="Search employees" placeholder="Search name, number or position" class="sm:col-span-2 xl:col-span-1" />
            <x-ui.select wire:model.live="status" id="employees-status" aria-label="Filter by status" placeholder="All statuses" :options="$statuses" />
            <x-ui.select wire:model.live="account" id="employees-account" aria-label="Filter by login account" placeholder="With or without account" :options="['yes' => 'Has an account', 'no' => 'No account']" />
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <x-ui.checkbox wire:model.live="archived" id="employees-archived" label="Show archived" />
            @if ($filtered)
                <x-ui.button variant="ghost" size="sm" icon="x" wire:click="clearFilters">Clear filters</x-ui.button>
            @endif
        </div>
    </div>

    <div class="relative">
        <x-ui.loading variant="overlay" target="search,status,account,archived,clearFilters,archive,restore,previousPage,nextPage" />

        <x-ui.table>
            <x-slot:head>
                <x-ui.table.th class="hidden sm:table-cell">Employee no.</x-ui.table.th>
                <x-ui.table.th>Name</x-ui.table.th>
                <x-ui.table.th class="hidden md:table-cell">Position</x-ui.table.th>
                <x-ui.table.th class="hidden sm:table-cell">Status</x-ui.table.th>
                <x-ui.table.th class="hidden lg:table-cell">Account</x-ui.table.th>
                <x-ui.table.th class="hidden xl:table-cell">Hire date</x-ui.table.th>
                <x-ui.table.th align="right"><span class="sr-only">Actions</span></x-ui.table.th>
            </x-slot:head>

            @forelse ($employees as $employee)
                <x-ui.table.tr wire:key="employee-{{ $employee->id }}">
                    <x-ui.table.td class="hidden font-mono text-xs whitespace-nowrap text-zinc-500 sm:table-cell dark:text-zinc-400">{{ $employee->employee_number }}</x-ui.table.td>
                    <x-ui.table.td>
                        <a href="{{ route('employees.show', $employee) }}" class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">{{ $employee->full_name }}</a>
                        <p class="mt-0.5 text-xs text-zinc-500 md:hidden dark:text-zinc-400">
                            <span class="font-mono sm:hidden">{{ $employee->employee_number }} · </span>{{ $employee->position }}
                        </p>
                        <p class="mt-1 flex flex-wrap gap-1 sm:hidden">
                            <x-ui.badge :variant="$employee->status->badge()">{{ $employee->status->label() }}</x-ui.badge>
                            @if ($employee->trashed())
                                <x-ui.badge variant="warning">Archived</x-ui.badge>
                            @endif
                        </p>
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden md:table-cell">{{ $employee->position }}</x-ui.table.td>
                    <x-ui.table.td class="hidden sm:table-cell">
                        <div class="flex flex-wrap gap-1">
                            <x-ui.badge :variant="$employee->status->badge()">{{ $employee->status->label() }}</x-ui.badge>
                            @if ($employee->trashed())
                                <x-ui.badge variant="warning">Archived</x-ui.badge>
                            @endif
                        </div>
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden lg:table-cell">
                        @if ($employee->user)
                            <span class="block text-sm break-all">{{ $employee->user->email }}</span>
                            <x-ui.badge :variant="$employee->user->status->badge()" class="mt-0.5">{{ $employee->user->status->label() }}</x-ui.badge>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-500">No account</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden whitespace-nowrap text-zinc-500 xl:table-cell dark:text-zinc-400">{{ $employee->hire_date?->format('M j, Y') ?? '—' }}</x-ui.table.td>
                    <x-ui.table.td align="right">
                        <div class="flex items-center justify-end gap-0.5">
                            <x-ui.icon-button :href="route('employees.show', $employee)" icon="eye" :label="'View '.$employee->full_name" />
                            @if ($employee->trashed())
                                @can('restore', $employee)
                                    <x-ui.icon-button icon="restore" :label="'Restore '.$employee->full_name" wire:click="restore({{ $employee->id }})" />
                                @endcan
                            @else
                                @can('update', $employee)
                                    <x-ui.icon-button :href="route('employees.edit', $employee)" icon="pencil" :label="'Edit '.$employee->full_name" />
                                @endcan
                                @can('delete', $employee)
                                    <x-ui.icon-button
                                        icon="archive"
                                        :label="'Archive '.$employee->full_name"
                                        danger
                                        x-on:click="$dispatch('confirm', {
                                            title: {{ \Illuminate\Support\Js::from('Archive '.$employee->full_name.'?') }},
                                            message: 'The record moves to the archive and can be restored. A linked login account is not changed.',
                                            confirmLabel: 'Archive',
                                            danger: true,
                                            action: () => $wire.archive({{ $employee->id }}),
                                        })"
                                    />
                                @endcan
                            @endif
                        </div>
                    </x-ui.table.td>
                </x-ui.table.tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state icon="employees" :title="$archived ? 'No archived employees' : 'No employees found'" :message="$filtered ? 'Try different filters.' : 'Add your first employee to get started.'">
                            @if ($filtered)
                                <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">Clear filters</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <x-ui.pagination :paginator="$employees" class="mt-4" />
</div>
