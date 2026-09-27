<div>
    <x-ui.page-header title="Privileges" description="The permissions that roles are built from, grouped by module.">
        @can('create', \App\Models\Permission::class)
            <x-slot:actions>
                <x-ui.button icon="plus" wire:click="create">Add privilege</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="mb-4 max-w-md">
        <x-ui.search-input wire:model.live.debounce.400ms="search" id="permissions-search" label="Search privileges" placeholder="Search privileges" />
    </div>

    <div class="relative space-y-4">
        <x-ui.loading variant="overlay" target="search,delete" />

        @forelse ($groups as $module => $group)
            <x-ui.card :padding="false" wire:key="module-{{ $module }}">
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $group['label'] }}</h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count privilege|:count privileges', count($group['permissions'])) }}</span>
                </x-slot:header>

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($group['permissions'] as $permission)
                        <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="permission-{{ $permission->id }}">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="font-mono text-sm break-all text-zinc-900 dark:text-zinc-100">{{ $permission->name }}</code>
                                    @if ($permission->isSystem())
                                        <x-ui.badge>System</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="info">Custom</x-ui.badge>
                                    @endif
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse ($permission->roles as $role)
                                        <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                                    @empty
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">Not used by any role</span>
                                    @endforelse
                                </div>
                            </div>

                            @unless ($permission->isSystem())
                                <div class="flex shrink-0 items-center gap-0.5">
                                    @can('update', $permission)
                                        <x-ui.icon-button icon="pencil" :label="'Rename '.$permission->name" wire:click="edit({{ $permission->id }})" />
                                    @endcan
                                    @can('delete', $permission)
                                        <x-ui.icon-button
                                            icon="trash"
                                            :label="'Delete '.$permission->name"
                                            danger
                                            x-on:click="$dispatch('confirm', {
                                                title: {{ \Illuminate\Support\Js::from('Delete '.$permission->name.'?') }},
                                                message: {{ \Illuminate\Support\Js::from($permission->roles->isNotEmpty() ? 'It is used by '.$permission->roles->pluck('name')->implode(', ').', so it cannot be deleted until it is removed from those roles.' : 'No role uses it. This cannot be undone.') }},
                                                confirmLabel: 'Delete',
                                                danger: true,
                                                action: () => $wire.delete({{ $permission->id }}),
                                            })"
                                        />
                                    @endcan
                                </div>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @empty
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="key" title="No privileges found" message="Try a different search." />
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Rename privilege' : 'Add privilege'" description="Use the module.action format, for example reports_export.run." max-width="md">
        <form wire:submit="save" id="permission-form">
            <x-ui.input label="Privilege name" wire:model="name" placeholder="module.action" autocomplete="off" maxlength="100" required />
        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Cancel</x-ui.button>
            <x-ui.button type="submit" form="permission-form" loading="save">{{ $editingId ? 'Save' : 'Create privilege' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
