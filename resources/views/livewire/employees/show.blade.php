<div>
    <x-ui.page-header :title="$employee->full_name" :description="$employee->employee_number.' · '.$employee->position">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('employees.index')">Back to employees</x-ui.button>
            @can('update', $employee)
                <x-ui.button icon="pencil" :href="route('employees.edit', $employee)">Edit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($employee->trashed())
        <x-ui.alert variant="warning" class="mb-6">This employee is archived. Restore them from the Employees list to make changes.</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-slot:header>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Details</h2>
                <x-ui.badge :variant="$employee->status->badge()">{{ $employee->status->label() }}</x-ui.badge>
            </x-slot:header>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                @foreach ([
                    'Employee no.' => $employee->employee_number,
                    'Full name' => $employee->full_name,
                    'Position' => $employee->position,
                    'Hire date' => $employee->hire_date?->format('F j, Y'),
                    'Contact email' => $employee->email,
                    'Phone' => $employee->phone,
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="mt-1 text-sm break-words text-zinc-900 dark:text-zinc-100">{{ filled($value) ? $value : '—' }}</dd>
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Address</dt>
                    <dd class="mt-1 text-sm whitespace-pre-line text-zinc-900 dark:text-zinc-100">{{ $employee->address ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Notes</dt>
                    <dd class="mt-1 text-sm whitespace-pre-line text-zinc-900 dark:text-zinc-100">{{ $employee->notes ?: '—' }}</dd>
                </div>
            </dl>

            <x-slot:footer class="justify-start text-xs text-zinc-500 dark:text-zinc-400">
                Added {{ $employee->created_at?->format('M j, Y') }}{{ $employee->creator ? ' by '.$employee->creator->name : '' }}
                · Updated {{ $employee->updated_at?->format('M j, Y') }}{{ $employee->updater ? ' by '.$employee->updater->name : '' }}
            </x-slot:footer>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Login account</h2>
                </x-slot:header>

                @if ($employee->user)
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Email</dt>
                            <dd class="mt-1 break-all text-zinc-900 dark:text-zinc-100">{{ $employee->user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Roles</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                @forelse ($employee->user->getRoleNames() as $role)
                                    <x-ui.badge variant="accent">{{ $role }}</x-ui.badge>
                                @empty
                                    <span class="text-zinc-400">No roles</span>
                                @endforelse
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Status</dt>
                            <dd class="mt-1"><x-ui.badge :variant="$employee->user->status->badge()">{{ $employee->user->status->label() }}</x-ui.badge></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Last login</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $employee->user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                        </div>
                    </dl>
                    @can('view', $employee->user)
                        <x-ui.button variant="secondary" size="sm" class="mt-5" icon="users" :href="route('admin.users.show', $employee->user)">Open user account</x-ui.button>
                    @endcan
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">This employee has no login account.</p>
                @endif
            </x-ui.card>

            @if ($activity !== null)
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recent activity</h2>
                    </x-slot:header>

                    @if ($activity->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No activity recorded yet.</p>
                    @else
                        <ol class="space-y-4" data-test="recent-activity">
                            @foreach ($activity as $entry)
                                <li class="text-sm">
                                    <div class="flex items-center gap-2">
                                        <x-ui.badge :variant="$entry->event->badge()">{{ $entry->event->label() }}</x-ui.badge>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $entry->created_at?->format('M j, Y g:i A') }}</span>
                                    </div>
                                    <p class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $entry->description }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">by {{ $entry->actorName() }}</p>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
