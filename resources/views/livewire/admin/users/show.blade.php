<div>
    <x-ui.page-header :title="$user->name" :description="$user->email">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('admin.users.index')">Back to users</x-ui.button>
            @can('resetPassword', $user)
                <x-ui.button variant="secondary" icon="key" :href="route('admin.users.reset-password', $user)">Reset password</x-ui.button>
            @endcan
            @can('update', $user)
                <x-ui.button icon="pencil" :href="route('admin.users.edit', $user)">Edit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-slot:header>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Profile</h2>
            </x-slot:header>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Name</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Email</dt>
                    <dd class="mt-1 text-sm break-all text-zinc-900 dark:text-zinc-100">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Roles</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($roles as $role)
                            <x-ui.badge variant="accent">{{ $role }}</x-ui.badge>
                        @empty
                            <span class="text-sm text-zinc-400">No roles</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Status</dt>
                    <dd class="mt-1"><x-ui.badge :variant="$user->status->badge()">{{ $user->status->label() }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Email verified</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $user->email_verified_at?->format('M j, Y g:i A') ?? 'Not yet' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Last login</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Employee record</h2>
                </x-slot:header>
                @if ($user->employee)
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $user->employee->full_name }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->employee->employee_number }} · {{ $user->employee->position }}</p>
                    @can('view', $user->employee)
                        <x-ui.button variant="secondary" size="sm" class="mt-4" icon="employees" :href="route('employees.show', $user->employee)">Open employee record</x-ui.button>
                    @endcan
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Not linked to an employee.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Record</h2>
                </x-slot:header>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Created</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $user->created_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Last updated</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $user->updated_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>
</div>
