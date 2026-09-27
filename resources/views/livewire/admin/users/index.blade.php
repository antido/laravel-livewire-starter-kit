<div>
    <x-ui.page-header title="Users" description="Accounts that can sign in, their roles and their status.">
        @can('create', \App\Models\User::class)
            <x-slot:actions>
                <x-ui.button :href="route('admin.users.create')" icon="plus">Add user</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]">
        <x-ui.search-input wire:model.live.debounce.400ms="search" id="users-search" label="Search users" placeholder="Search by name or email" class="sm:col-span-2 lg:col-span-1" />
        <x-ui.select wire:model.live="status" id="users-status" aria-label="Filter by status" placeholder="All statuses" :options="$statuses" />
        <x-ui.select wire:model.live="role" id="users-role" aria-label="Filter by role" placeholder="All roles" :options="$roles->mapWithKeys(fn ($name) => [$name => $name])->all()" />
        @if ($search !== '' || $status !== '' || $role !== '')
            <x-ui.button variant="ghost" wire:click="clearFilters">Clear</x-ui.button>
        @endif
    </div>

    <div class="relative">
        <x-ui.loading variant="overlay" target="search,status,role,sortBy,clearFilters,previousPage,nextPage,gotoPage" />

        <x-ui.table>
            <x-slot:head>
                <x-ui.table.th sortable :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">Name</x-ui.table.th>
                <x-ui.table.th class="hidden md:table-cell">Roles</x-ui.table.th>
                <x-ui.table.th class="hidden xl:table-cell">Employee</x-ui.table.th>
                <x-ui.table.th class="hidden sm:table-cell">Status</x-ui.table.th>
                <x-ui.table.th sortable :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')" class="hidden sm:table-cell">Created</x-ui.table.th>
                <x-ui.table.th align="right"><span class="sr-only">Actions</span></x-ui.table.th>
            </x-slot:head>

            @forelse ($users as $user)
                <x-ui.table.tr wire:key="user-{{ $user->id }}">
                    <x-ui.table.td>
                        <div class="flex min-w-0 items-center gap-3">
                            <x-ui.avatar :name="$user->name" size="sm" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.show', $user) }}" class="block truncate font-medium text-zinc-900 hover:underline dark:text-zinc-100">{{ $user->name }}</a>
                                <p class="text-xs break-all text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                <p class="mt-1 flex flex-wrap gap-1 md:hidden">
                                    <x-ui.badge :variant="$user->status->badge()" class="sm:hidden">{{ $user->status->label() }}</x-ui.badge>
                                    @foreach ($user->roles as $userRole)
                                        <x-ui.badge>{{ $userRole->name }}</x-ui.badge>
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden md:table-cell">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->roles as $userRole)
                                <x-ui.badge>{{ $userRole->name }}</x-ui.badge>
                            @empty
                                <span class="text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden xl:table-cell">
                        @if ($user->employee)
                            @can('view', $user->employee)
                                <a href="{{ route('employees.show', $user->employee) }}" class="hover:underline">{{ $user->employee->full_name }}</a>
                            @else
                                {{ $user->employee->full_name }}
                            @endcan
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden sm:table-cell">
                        <x-ui.badge :variant="$user->status->badge()">{{ $user->status->label() }}</x-ui.badge>
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden whitespace-nowrap text-zinc-500 sm:table-cell dark:text-zinc-400">{{ $user->created_at?->format('M j, Y') }}</x-ui.table.td>
                    <x-ui.table.td align="right">
                        <div class="flex flex-wrap items-center justify-end gap-0.5">
                            <x-ui.icon-button :href="route('admin.users.show', $user)" icon="eye" :label="'View '.$user->name" />
                            @can('update', $user)
                                <x-ui.icon-button :href="route('admin.users.edit', $user)" icon="pencil" :label="'Edit '.$user->name" />
                            @endcan
                            @can('resetPassword', $user)
                                <x-ui.icon-button :href="route('admin.users.reset-password', $user)" icon="key" :label="'Reset password for '.$user->name" />
                            @endcan
                            @if ($actor->isNot($user))
                                @can('changeStatus', $user)
                                    <x-ui.icon-button
                                        icon="power"
                                        :label="($user->isActive() ? 'Deactivate ' : 'Activate ').$user->name"
                                        :danger="$user->isActive()"
                                        x-on:click="$dispatch('confirm', {
                                            title: {{ \Illuminate\Support\Js::from(($user->isActive() ? 'Deactivate ' : 'Activate ').$user->name.'?') }},
                                            message: {{ \Illuminate\Support\Js::from($user->isActive() ? 'They will be signed out and can no longer log in.' : 'They will be able to log in again.') }},
                                            confirmLabel: {{ \Illuminate\Support\Js::from($user->isActive() ? 'Deactivate' : 'Activate') }},
                                            danger: {{ $user->isActive() ? 'true' : 'false' }},
                                            action: () => $wire.toggleStatus({{ $user->id }}),
                                        })"
                                    />
                                @endcan
                                @can('delete', $user)
                                    <x-ui.icon-button
                                        icon="trash"
                                        :label="'Delete '.$user->name"
                                        danger
                                        x-on:click="$dispatch('confirm', {
                                            title: {{ \Illuminate\Support\Js::from('Delete '.$user->name.'?') }},
                                            message: {{ \Illuminate\Support\Js::from($user->employee ? 'The account is removed for good. The employee record of '.$user->employee->full_name.' is kept, without a login.' : 'The account is removed for good. This cannot be undone.') }},
                                            confirmLabel: 'Delete user',
                                            danger: true,
                                            action: () => $wire.delete({{ $user->id }}),
                                        })"
                                    />
                                @endcan
                            @endif
                        </div>
                    </x-ui.table.td>
                </x-ui.table.tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state icon="users" title="No users found" message="Try a different search or clear the filters.">
                            @if ($search !== '' || $status !== '' || $role !== '')
                                <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">Clear filters</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <x-ui.pagination :paginator="$users" class="mt-4" />
</div>
