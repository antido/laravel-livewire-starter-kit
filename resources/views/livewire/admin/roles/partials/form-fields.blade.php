{{-- Shared by the role create and edit pages. Expects $groups; on edit also $role. --}}
@php
    $isSystem = isset($role) && $role->isSystem();
    $isSuperAdminRole = isset($role) && $role->isSuperAdmin();
@endphp

<div class="max-w-md">
    <x-ui.input
        label="Role name"
        wire:model="name"
        maxlength="50"
        :readonly="$isSystem"
        :hint="$isSystem ? 'System roles cannot be renamed.' : null"
        required
    />
</div>

<fieldset class="mt-8">
    <legend class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Permissions</legend>

    @if ($isSuperAdminRole)
        <x-ui.alert variant="info" class="mt-3">The Super Admin role passes every permission check, so its permissions cannot be edited.</x-ui.alert>
    @else
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">You can only grant permissions you have yourself.</p>
    @endif

    @error('permissions')
        <x-ui.alert variant="danger" class="mt-3">{{ $message }}</x-ui.alert>
    @enderror
    @error('permissions.*')
        <x-ui.alert variant="danger" class="mt-3">{{ $message }}</x-ui.alert>
    @enderror

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($groups as $module => $group)
            @php
                $grantable = collect($group['permissions'])->where('grantable', true)->pluck('name');
                $allSelected = $grantable->isNotEmpty() && $grantable->diff($permissions)->isEmpty();
            @endphp
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800" wire:key="module-{{ $module }}">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $group['label'] }}</h3>
                    @unless ($isSuperAdminRole || $grantable->isEmpty())
                        <button
                            type="button"
                            wire:click="toggleModule(@js($module))"
                            class="rounded text-xs font-medium text-accent-700 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 dark:text-accent-400"
                            aria-label="{{ ($allSelected ? 'Clear all ' : 'Select all ').$group['label'].' permissions' }}"
                        >{{ $allSelected ? 'Clear all' : 'Select all' }}</button>
                    @endunless
                </div>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($group['permissions'] as $permission)
                        <x-ui.checkbox
                            wire:model.live="permissions"
                            value="{{ $permission['name'] }}"
                            id="permission-{{ str_replace(['.', '_'], '-', $permission['name']) }}"
                            :label="$permission['label']"
                            :disabled="$isSuperAdminRole || ! $permission['grantable']"
                            :title="$permission['grantable'] ? $permission['name'] : $permission['name'].' (you do not have this permission)'"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</fieldset>
