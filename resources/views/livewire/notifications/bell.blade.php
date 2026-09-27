<div wire:poll.30s.visible="refreshCount">
    <x-ui.dropdown
        :label="'Notifications, '.$unread.' unread'"
        width="w-80 sm:w-96"
        panel-class="max-sm:fixed max-sm:inset-x-3 max-sm:top-16 max-sm:mt-0 max-sm:w-auto max-sm:max-w-none"
        data-test="notification-bell"
    >
        <x-slot:trigger>
            <span class="relative grid size-9 place-items-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" x-on:click="$wire.loaded || $wire.load()">
                <x-ui.icon name="bell" class="size-5" />
                @if ($unread > 0)
                    <span class="absolute -top-0.5 -right-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-600 px-1 text-[10px] leading-none font-semibold text-white ring-2 ring-white dark:ring-zinc-900" data-test="notification-count">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </span>
        </x-slot:trigger>

        <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-3 py-2.5 dark:border-zinc-800">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Notifications</p>
            @if ($unread > 0)
                <button type="button" role="menuitem" wire:click="markAllAsRead" class="rounded text-xs font-medium text-accent-700 hover:underline focus-visible:outline-2 focus-visible:outline-accent-600 dark:text-accent-400">Mark all as read</button>
            @endif
        </div>

        <div class="max-h-[60vh] overflow-y-auto py-1" wire:loading.class="opacity-60" wire:target="load">
            @if (! $loaded)
                <div class="flex items-center justify-center py-8"><x-ui.spinner class="text-accent-600" /></div>
            @else
                @forelse ($latest as $notification)
                    @php($type = \App\Enums\NotificationType::tryFrom($notification->data['type'] ?? '') ?? \App\Enums\NotificationType::Info)
                    <button
                        type="button"
                        role="menuitem"
                        wire:key="bell-{{ $notification->id }}"
                        wire:click="visit('{{ $notification->id }}')"
                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                        data-test="notification-item"
                    >
                        <span @class(['mt-0.5 grid size-8 shrink-0 place-items-center rounded-full', $type->classes()])>
                            <x-ui.icon :name="$type->icon()" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block truncate text-sm', 'font-semibold text-zinc-900 dark:text-zinc-100' => $notification->read_at === null, 'font-medium text-zinc-700 dark:text-zinc-300' => $notification->read_at !== null])>{{ $notification->data['title'] ?? 'Notification' }}</span>
                            <span class="mt-0.5 line-clamp-2 block text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-zinc-400 dark:text-zinc-500">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                        @if ($notification->read_at === null)
                            <span class="mt-2 size-2 shrink-0 rounded-full bg-accent-600 dark:bg-accent-400"><span class="sr-only">Unread</span></span>
                        @endif
                    </button>
                @empty
                    <div class="px-3 py-8 text-center">
                        <x-ui.icon name="check-circle" class="mx-auto size-6 text-zinc-400" />
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">You're all caught up.</p>
                    </div>
                @endforelse
            @endif
        </div>

        <div class="border-t border-zinc-200 p-1 dark:border-zinc-800">
            <a href="{{ route('notifications.index') }}" role="menuitem" class="block rounded-lg px-3 py-2 text-center text-sm font-medium text-accent-700 hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:text-accent-400 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800">View all</a>
        </div>
    </x-ui.dropdown>
</div>
