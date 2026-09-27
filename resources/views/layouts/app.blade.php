<!DOCTYPE html>
<html @include('partials.html-attributes')>
<head>
    @include('partials.head', ['title' => $title ?? null])
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg dark:focus:bg-zinc-900">Skip to content</a>

    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-zinc-200 bg-white lg:flex dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex h-16 shrink-0 items-center px-5">
            <x-app.logo :href="route('dashboard')" />
        </div>
        <div class="flex-1 overflow-y-auto px-3 py-4">
            <x-app.sidebar-nav />
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Main menu">
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="sidebarOpen = false"></div>
        <aside
            x-show="sidebarOpen"
            x-trap.noscroll="sidebarOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-white shadow-xl dark:bg-zinc-900"
        >
            <div class="flex h-16 shrink-0 items-center justify-between px-5">
                <x-app.logo :href="route('dashboard')" />
                <button type="button" x-on:click="sidebarOpen = false" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close menu">
                    <x-ui.icon name="x" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-3 py-4">
                <x-app.sidebar-nav />
            </div>
        </aside>
    </div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/85 px-4 backdrop-blur sm:px-6 dark:border-zinc-800 dark:bg-zinc-900/85">
            <button type="button" x-on:click="sidebarOpen = true" class="-ml-1 rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-accent-600 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Open menu" x-bind:aria-expanded="sidebarOpen.toString()">
                <x-ui.icon name="menu" />
            </button>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200" data-test="header-title">{{ $title ?? config('app.name') }}</p>
            </div>

            <div class="flex items-center gap-1 sm:gap-2">
                <livewire:components.theme-toggle />
                <livewire:notifications.bell />
                <x-app.user-menu />
            </div>
        </header>

        <main id="main" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <x-app.breadcrumbs />
            {{ $slot }}
        </main>
    </div>

    <x-ui.toast />
    <x-ui.confirm-dialog />
    @livewireScripts
</body>
</html>
