@props(['href'])

<a href="{{ $href }}" {{ $attributes->class('font-medium text-accent-700 underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 dark:text-accent-400') }}>{{ $slot }}</a>
