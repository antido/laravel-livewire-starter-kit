{{-- Shared by the create and edit pages. Expects $roleOptions, $statuses and, on edit, $isSelf. --}}
@php($isSelf = $isSelf ?? false)

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-ui.input label="Full name" wire:model="name" autocomplete="off" required />
    <x-ui.input label="Email address" type="email" wire:model="email" autocomplete="off" required />
</div>

<fieldset class="mt-6">
    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Roles</legend>
    @if ($isSelf)
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">You cannot change your own roles.</p>
    @else
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Only roles within your own permissions can be assigned.</p>
    @endif

    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($roleOptions as $option)
            <x-ui.checkbox
                wire:model="roles"
                value="{{ $option['name'] }}"
                :label="$option['name']"
                :description="$option['assignable'] ? null : 'You cannot assign this role.'"
                :disabled="$isSelf || ! $option['assignable']"
            />
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">There are no roles you can assign.</p>
        @endforelse
    </div>
    @error('roles')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error('roles.*')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</fieldset>

<div class="mt-6 max-w-xs">
    <x-ui.select label="Status" wire:model="status" :options="$statuses" :disabled="$isSelf" :hint="$isSelf ? 'You cannot change your own status.' : 'Inactive users cannot log in.'" />
</div>
