<div>
    <x-ui.page-header title="Notifications" description="Updates about your account and your team.">
        @if ($unread > 0)
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="check" wire:click="markAllAsRead">Mark all as read</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800" role="tablist" aria-label="Filter notifications">
        @foreach (['all' => 'All', 'unread' => 'Unread'] as $key => $label)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                wire:click="showTab('{{ $key }}')"
                @class([
                    'rounded-md px-4 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-accent-600',
                    'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100' => $tab === $key,
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== $key,
                ])
            >
                {{ $label }}
                @if ($key === 'unread' && $unread > 0)
                    <span class="ml-1 rounded-full bg-accent-100 px-1.5 text-xs text-accent-800 dark:bg-accent-500/15 dark:text-accent-300">{{ $unread }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <x-ui.card :padding="false" class="relative">
        <x-ui.loading variant="overlay" target="showTab,markAllAsRead,markAsRead,markAsUnread,delete,previousPage,nextPage" />

        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" role="list">
            @forelse ($notifications as $notification)
                @php($type = \App\Enums\NotificationType::tryFrom($notification->data['type'] ?? '') ?? \App\Enums\NotificationType::Info)
                <li wire:key="notification-{{ $notification->id }}" @class(['flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:px-5', 'bg-accent-50/40 dark:bg-accent-500/5' => $notification->read_at === null])>
                    <button type="button" wire:click="open('{{ $notification->id }}')" class="flex min-w-0 flex-1 items-start gap-3 rounded-lg text-left focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent-600" data-test="notification-row">
                        <span @class(['grid size-9 shrink-0 place-items-center rounded-full', $type->classes()])>
                            <x-ui.icon :name="$type->icon()" class="size-4" />
                        </span>
                        <span class="min-w-0">
                            <span @class(['block text-sm', 'font-semibold text-zinc-900 dark:text-zinc-100' => $notification->read_at === null, 'font-medium text-zinc-700 dark:text-zinc-300' => $notification->read_at !== null])>
                                {{ $notification->data['title'] ?? 'Notification' }}
                                @if ($notification->read_at === null)
                                    <span class="ml-1 inline-block size-2 rounded-full bg-accent-600 align-middle dark:bg-accent-400"><span class="sr-only">Unread</span></span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-sm text-zinc-600 dark:text-zinc-400">{{ $notification->data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-zinc-400 dark:text-zinc-500" title="{{ $notification->created_at?->format('M j, Y g:i A') }}">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                    </button>

                    <div class="flex shrink-0 items-center gap-0.5 self-end sm:self-start">
                        @if ($notification->read_at === null)
                            <x-ui.icon-button icon="check" label="Mark as read" wire:click="markAsRead('{{ $notification->id }}')" />
                        @else
                            <x-ui.icon-button icon="inbox" label="Mark as unread" wire:click="markAsUnread('{{ $notification->id }}')" />
                        @endif
                        <x-ui.icon-button icon="trash" label="Delete notification" danger wire:click="delete('{{ $notification->id }}')" />
                    </div>
                </li>
            @empty
                <li>
                    <x-ui.empty-state icon="bell" :title="$tab === 'unread' ? 'No unread notifications' : 'No notifications yet'" message="You're all caught up." />
                </li>
            @endforelse
        </ul>
    </x-ui.card>

    <x-ui.pagination :paginator="$notifications" class="mt-4" />
</div>
