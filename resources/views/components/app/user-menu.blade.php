@php($user = auth()->user())

@if ($user)
    <x-ui.dropdown label="Account menu for {{ $user->name }}" width="w-64" data-test="user-menu">
        <x-slot:trigger>
            <span class="flex items-center gap-2 rounded-lg p-1 transition hover:bg-zinc-100 sm:pr-2 dark:hover:bg-zinc-800">
                <x-ui.avatar :name="$user->name" size="sm" />
                <span class="hidden max-w-40 truncate text-sm font-medium text-zinc-700 sm:block dark:text-zinc-200">{{ $user->name }}</span>
                <x-ui.icon name="chevron-down" class="hidden size-4 text-zinc-400 sm:block" />
            </span>
        </x-slot:trigger>

        <div class="flex items-center gap-3 border-b border-zinc-200 px-3 py-3 dark:border-zinc-800">
            <x-ui.avatar :name="$user->name" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
            </div>
        </div>

        <div class="py-1">
            @if (Route::has('settings.appearance'))
                <x-ui.dropdown-item :href="route('settings.appearance')" icon="settings">Settings</x-ui.dropdown-item>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.dropdown-item type="submit" icon="logout">Log out</x-ui.dropdown-item>
            </form>
        </div>
    </x-ui.dropdown>
@endif
