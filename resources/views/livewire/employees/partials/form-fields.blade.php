{{--
    Shared by the employee create and edit pages. Expects $statuses, $canManageAccounts, $canCreateAccounts,
    $canLinkAccounts, $roleOptions and $linkableUsers; on edit also $linkedUser, $canUnlink and $showDeactivate.
--}}
@php
    $linkedUser = $linkedUser ?? null;
    $showDeactivate = $showDeactivate ?? false;
@endphp

<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Employee details</h2>
        @isset($employee)
            @if ($employee)
                <x-ui.badge>{{ $employee->employee_number }}</x-ui.badge>
            @endif
        @endisset
    </x-slot:header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input label="First name" wire:model="form.first_name" autocomplete="off" required />
        <x-ui.input label="Middle name" wire:model="form.middle_name" autocomplete="off" />
        <x-ui.input label="Last name" wire:model="form.last_name" autocomplete="off" required />
        <x-ui.input label="Suffix" wire:model="form.suffix" autocomplete="off" placeholder="Jr., III" />
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.input label="Position" wire:model="form.position" autocomplete="off" required />
        <x-ui.input label="Hire date" type="date" wire:model="form.hire_date" :max="now()->format('Y-m-d')" />
        <x-ui.select label="Status" wire:model.live="form.status" :options="$statuses" />
    </div>

    @if ($showDeactivate)
        <div class="mt-4">
            <x-ui.alert variant="warning">
                <x-ui.checkbox wire:model="form.deactivate_account" label="Also deactivate the linked user account" :description="'The login '.$linkedUser?->email.' will stop working.'" />
            </x-ui.alert>
        </div>
    @endif

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input label="Contact email" type="email" wire:model="form.email" autocomplete="off" hint="Can differ from the login email." />
        <x-ui.input label="Phone" type="tel" wire:model="form.phone" autocomplete="off" placeholder="0917 123 4567" />
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-ui.textarea label="Address" wire:model="form.address" rows="3" />
        <x-ui.textarea label="Notes" wire:model="form.notes" rows="3" />
    </div>
</x-ui.card>

@if ($canManageAccounts || $linkedUser)
    <x-ui.card class="mt-6">
        <x-slot:header>
            <div>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">System access</h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Optional. A login account lets the employee sign in with the roles you give it.</p>
            </div>
        </x-slot:header>

        @if ($linkedUser)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-ui.avatar :name="$linkedUser->name" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium break-all text-zinc-900 dark:text-zinc-100">{{ $linkedUser->email }}</p>
                        <p class="mt-0.5 flex flex-wrap gap-1">
                            <x-ui.badge :variant="$linkedUser->status->badge()">{{ $linkedUser->status->label() }}</x-ui.badge>
                            @foreach ($linkedUser->getRoleNames() as $roleName)
                                <x-ui.badge>{{ $roleName }}</x-ui.badge>
                            @endforeach
                        </p>
                    </div>
                </div>
                @if ($canUnlink ?? false)
                    <x-ui.button
                        variant="secondary"
                        icon="unlink"
                        x-on:click="$dispatch('confirm', {
                            title: 'Unlink the login account?',
                            message: {{ \Illuminate\Support\Js::from('The account '.$linkedUser->email.' is kept, but it will no longer belong to this employee.') }},
                            confirmLabel: 'Unlink',
                            action: () => $wire.unlink(),
                        })"
                    >Unlink account</x-ui.button>
                @endif
            </div>
        @else
            <fieldset>
                <legend class="sr-only">Login account</legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach (['none' => ['No login account', 'The employee does not use the system.', true], 'create' => ['Create a new account', 'Set a login email, password and roles.', $canCreateAccounts], 'link' => ['Link an existing user', 'Pick a user who has no employee yet.', $canLinkAccounts]] as $mode => [$modeLabel, $modeHint, $allowed])
                        @if ($allowed)
                            <label @class([
                                'flex cursor-pointer gap-3 rounded-xl border p-3 transition focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-600',
                                'border-accent-600 ring-1 ring-accent-600 dark:border-accent-500 dark:ring-accent-500' => $form->account_mode === $mode,
                                'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' => $form->account_mode !== $mode,
                            ])>
                                <input type="radio" name="account_mode" value="{{ $mode }}" wire:model.live="form.account_mode" class="mt-0.5 size-4 accent-accent-600">
                                <span>
                                    <span class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $modeLabel }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $modeHint }}</span>
                                </span>
                            </label>
                        @endif
                    @endforeach
                </div>
                @error('form.account_mode')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            @if ($form->account_mode === 'create')
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.input label="Login email" type="email" wire:model="form.account_email" autocomplete="off" required />
                    <x-ui.input label="Password" type="password" wire:model="form.account_password" autocomplete="new-password" hint="At least 8 characters." required />
                    <x-ui.input label="Confirm password" type="password" wire:model="form.account_password_confirmation" autocomplete="new-password" required />
                </div>
                <fieldset class="mt-4">
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Roles</legend>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">The account's name is taken from the employee's full name.</p>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                        @forelse ($roleOptions as $roleName)
                            <x-ui.checkbox wire:model="form.account_roles" value="{{ $roleName }}" id="account-role-{{ \Illuminate\Support\Str::slug($roleName) }}" :label="$roleName" />
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">There are no roles you can assign.</p>
                        @endforelse
                    </div>
                    @error('form.account_roles')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </fieldset>
            @endif

            @if ($form->account_mode === 'link')
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.search-input wire:model.live.debounce.300ms="linkSearch" id="link-search" label="Search users without an employee" placeholder="Search by name or email" />
                    <x-ui.select wire:model="form.link_user_id" id="link-user" aria-label="User to link" placeholder="Choose a user" error="form.link_user_id">
                        @foreach ($linkableUsers as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                @if ($linkableUsers->isEmpty())
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No users without an employee match your search.</p>
                @endif
            @endif
        @endif
    </x-ui.card>
@endif
