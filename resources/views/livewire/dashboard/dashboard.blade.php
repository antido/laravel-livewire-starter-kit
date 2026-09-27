<div class="mx-auto max-w-4xl py-4 sm:py-10">
    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl dark:text-zinc-50" data-test="greeting">
        {{ $greeting }}, {{ $user->firstName() }} <span aria-hidden="true">👋</span>
    </h1>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
        <span>{{ $today }}</span>
        @foreach ($roles as $role)
            <span aria-hidden="true">·</span>
            <x-ui.badge variant="accent">{{ $role }}</x-ui.badge>
        @endforeach
    </div>

    <p class="mt-8 max-w-xl text-zinc-600 dark:text-zinc-400">
        Welcome to {{ config('app.name') }}. Use the menu on the left to get started.
    </p>

    @if ($quickLinks !== [])
        <ul @class(['mt-10 grid grid-cols-1 gap-3 sm:grid-cols-2', 'lg:grid-cols-4' => count($quickLinks) === 4, 'lg:grid-cols-3' => count($quickLinks) === 3]) role="list" aria-label="Quick links">
            @foreach ($quickLinks as $link)
                <li>
                    <a
                        href="{{ route($link['route']) }}"
                        class="group flex h-full flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-accent-300 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-accent-500/40"
                        data-test="quick-link"
                    >
                        <x-ui.icon :name="$link['icon']" class="size-5 text-accent-700 dark:text-accent-400" />
                        <span class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $link['label'] }}</span>
                        <span class="mt-1 flex flex-1 items-end justify-between gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $link['description'] }}
                            <x-ui.icon name="arrow-right" class="size-4 shrink-0 transition group-hover:translate-x-0.5" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($unreadCount > 0)
        <p class="mt-10 text-sm text-zinc-600 dark:text-zinc-400" data-test="unread-notifications">
            You have {{ $unreadCount }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadCount) }}.
            <x-ui.link :href="route('notifications.index')">View <span aria-hidden="true">→</span></x-ui.link>
        </p>
    @endif
</div>
