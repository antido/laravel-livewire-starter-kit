<div>
    <x-ui.page-header title="Roles" description="Each role is a set of permissions. Users get what their roles allow.">
        @can('create', \App\Models\Role::class)
            <x-slot:actions>
                <x-ui.button :href="route('admin.roles.create')" icon="plus">Add role</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="mb-4 max-w-md">
        <x-ui.search-input wire:model.live.debounce.400ms="search" id="roles-search" label="Search roles" placeholder="Search roles" />
    </div>

    <div class="relative">
        <x-ui.loading variant="overlay" target="search,delete,previousPage,nextPage" />

        <x-ui.table>
            <x-slot:head>
                <x-ui.table.th>Role name</x-ui.table.th>
                <x-ui.table.th class="hidden sm:table-cell">Permissions</x-ui.table.th>
                <x-ui.table.th class="hidden sm:table-cell">Users</x-ui.table.th>
                <x-ui.table.th class="hidden md:table-cell">System</x-ui.table.th>
                <x-ui.table.th align="right"><span class="sr-only">Actions</span></x-ui.table.th>
            </x-slot:head>

            @forelse ($roles as $role)
                <x-ui.table.tr wire:key="role-{{ $role->id }}">
                    <x-ui.table.td>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $role->name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500 sm:hidden dark:text-zinc-400">
                            {{ $role->isSuperAdmin() ? 'All permissions' : trans_choice(':count permission|:count permissions', $role->permissions_count) }}
                            · {{ trans_choice(':count user|:count users', $role->users_count) }}
                        </p>
                        @if ($role->isSystem())
                            <x-ui.badge class="mt-1 md:hidden">System</x-ui.badge>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td class="hidden sm:table-cell">{{ $role->isSuperAdmin() ? 'All' : $role->permissions_count }}</x-ui.table.td>
                    <x-ui.table.td class="hidden sm:table-cell">{{ $role->users_count }}</x-ui.table.td>
                    <x-ui.table.td class="hidden md:table-cell">
                        @if ($role->isSystem())
                            <x-ui.badge>System</x-ui.badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td align="right">
                        <div class="flex items-center justify-end gap-0.5">
                            @can('update', $role)
                                <x-ui.icon-button :href="route('admin.roles.edit', $role)" icon="pencil" :label="'Edit '.$role->name" />
                            @endcan
                            {{-- System roles are never deletable; the Super Admin passes the policy, so check here too. --}}
                            @if (! $role->isSystem() && auth()->user()->can('delete', $role))
                                <x-ui.icon-button
                                    icon="trash"
                                    :label="'Delete '.$role->name"
                                    danger
                                    x-on:click="$dispatch('confirm', {
                                        title: {{ \Illuminate\Support\Js::from('Delete the '.$role->name.' role?') }},
                                        message: {{ \Illuminate\Support\Js::from($role->users_count > 0 ? 'It is assigned to '.trans_choice(':count user|:count users', $role->users_count).', so it cannot be deleted until it is removed from them.' : 'Nobody has this role. This cannot be undone.') }},
                                        confirmLabel: 'Delete role',
                                        danger: true,
                                        action: () => $wire.delete({{ $role->id }}),
                                    })"
                                />
                            @endif
                        </div>
                    </x-ui.table.td>
                </x-ui.table.tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-ui.empty-state icon="roles" title="No roles found" message="Try a different search." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <x-ui.pagination :paginator="$roles" class="mt-4" />
</div>
