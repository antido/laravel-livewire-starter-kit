{{-- Attributes for <html>: the language and, for signed-in users, their saved theme (read by partials.theme-script). --}}
lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-theme-preference="{{ auth()->user()->theme_preference->value }}" @endauth
