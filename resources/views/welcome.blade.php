@php
    $registrationEnabled = (bool) config('auth.registration.enabled');
    $features = [
        ['icon' => 'lock', 'title' => 'Secure sign-in', 'text' => 'Login, password reset and email verification built in.'],
        ['icon' => 'shield', 'title' => 'Roles & privileges', 'text' => 'Control exactly who can see and do what.'],
        ['icon' => 'employees', 'title' => 'Employee records', 'text' => 'Manage staff and link them to user accounts.'],
        ['icon' => 'audit', 'title' => 'Audit trails', 'text' => 'See who changed what, and when.'],
        ['icon' => 'bell', 'title' => 'Notifications', 'text' => 'Stay updated right from the top bar.'],
        ['icon' => 'moon', 'title' => 'Light & dark mode', 'text' => 'Comfortable to use, day or night.'],
    ];
@endphp
<!DOCTYPE html>
<html @include('partials.html-attributes')>
<head>
    @include('partials.head', [
        'title' => null,
        'description' => config('app.name').' is a clean foundation for business apps: secure sign-in, roles and privileges, employee records, audit trails, notifications and light or dark mode.',
    ])
</head>
<body class="min-h-screen" x-data>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg dark:focus:bg-zinc-900">Skip to content</a>

    <header class="sticky top-0 z-30 border-b border-zinc-200/70 bg-zinc-50/80 backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-950/80">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <x-app.logo />

            <nav class="flex shrink-0 items-center gap-1 sm:gap-2" aria-label="Account">
                <x-app.theme-toggle />
                @auth
                    <x-ui.button :href="route('dashboard')" size="sm">Go to dashboard</x-ui.button>
                @else
                    <x-ui.button :href="route('login')" variant="ghost" size="sm">Log in</x-ui.button>
                    @if ($registrationEnabled)
                        <x-ui.button :href="route('register')" size="sm">Sign up</x-ui.button>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main id="main">
        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-4 pt-20 pb-24 text-center sm:px-6 sm:pt-28 sm:pb-32" aria-labelledby="hero-title">
            <x-ui.badge variant="accent">Starter Kit</x-ui.badge>
            <h1 id="hero-title" class="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-6xl dark:text-zinc-50">
                A clean foundation for your next app.
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-lg text-pretty text-zinc-600 dark:text-zinc-400">
                Secure sign-in, roles, employees and audit trails — ready from day one.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <x-ui.button :href="route('dashboard')" size="lg">Go to dashboard</x-ui.button>
                @else
                    <x-ui.button :href="$registrationEnabled ? route('register') : route('login')" size="lg" data-test="get-started">Get started</x-ui.button>
                    <x-ui.button :href="route('login')" variant="secondary" size="lg">Log in</x-ui.button>
                @endauth
            </div>
        </section>

        {{-- Features --}}
        <section class="border-y border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50" aria-labelledby="features-title">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
                <h2 id="features-title" class="text-center text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50">Everything you need to start</h2>
                <p class="mx-auto mt-3 max-w-lg text-center text-zinc-600 dark:text-zinc-400">The groundwork every business app needs, done properly.</p>

                <ul class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($features as $feature)
                        <li class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-6 dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="grid size-10 place-items-center rounded-lg bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-400">
                                <x-ui.icon :name="$feature['icon']" class="size-5" />
                            </span>
                            <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $feature['title'] }}</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $feature['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Call to action --}}
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24" aria-labelledby="cta-title">
            <div class="flex flex-col items-center justify-between gap-6 rounded-2xl border border-zinc-200 bg-white px-6 py-10 text-center sm:flex-row sm:px-10 sm:text-left dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <h2 id="cta-title" class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Ready to get started?</h2>
                    <p class="mt-1 text-zinc-600 dark:text-zinc-400">Set up your team in a few minutes.</p>
                </div>
                @auth
                    <x-ui.button :href="route('dashboard')" size="lg">Go to dashboard</x-ui.button>
                @else
                    <x-ui.button :href="$registrationEnabled ? route('register') : route('login')" size="lg">{{ $registrationEnabled ? 'Create account' : 'Log in' }}</x-ui.button>
                @endauth
            </div>
        </section>
    </main>

    <footer class="border-t border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-zinc-500 sm:flex-row sm:px-6 dark:text-zinc-400">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}</p>
            @guest
                <nav class="flex items-center gap-4" aria-label="Footer">
                    <a href="{{ route('login') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">Log in</a>
                    @if ($registrationEnabled)
                        <a href="{{ route('register') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">Sign up</a>
                    @endif
                </nav>
            @endguest
        </div>
    </footer>

    @livewireScripts
</body>
</html>
