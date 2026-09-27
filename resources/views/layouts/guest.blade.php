<!DOCTYPE html>
<html @include('partials.html-attributes')>
<head>
    @include('partials.head', ['title' => $title ?? null])
</head>
<body class="flex min-h-screen flex-col" x-data>
    <header class="flex h-16 items-center justify-between px-4 sm:px-6">
        <x-app.logo />
        <x-app.theme-toggle />
    </header>

    <main class="flex flex-1 items-start justify-center px-4 pt-6 pb-16 sm:items-center sm:pt-0">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </main>

    <x-ui.toast />
    @livewireScripts
</body>
</html>
