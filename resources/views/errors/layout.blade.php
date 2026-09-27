{{--
    The layout for error pages. It deliberately avoids the database, the session user and Livewire, so it
    still renders when those are the problem. It never shows exception details.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title') · {{ config('app.name') }}</title>
    @include('partials.theme-script')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-50 px-4 py-16 text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
    <main class="w-full max-w-md text-center">
        <p class="text-sm font-semibold tracking-wide text-accent-700 dark:text-accent-400">Error @yield('code')</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl dark:text-zinc-50">@yield('title')</h1>
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">@yield('message')</p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ url('/') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-accent-700 px-4 text-sm font-medium text-white shadow-sm hover:bg-accent-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 dark:bg-accent-500 dark:text-zinc-950 dark:hover:bg-accent-400">Go to the home page</a>
            @yield('action')
        </div>
    </main>
</body>
</html>
