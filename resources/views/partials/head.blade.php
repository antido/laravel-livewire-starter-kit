{{-- Shared <head> content for every full page. Pass "title" to prefix the app name. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ filled($title ?? null) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
@isset($description)
    <meta name="description" content="{{ $description }}">
@endisset
@include('partials.theme-script')
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
