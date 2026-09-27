@props([
    // The Livewire action(s) to wait for; empty means any request from this component.
    'target' => null,
    'label' => 'Loading…',
    // "inline" shows a spinner and label; "overlay" covers the nearest positioned parent (give it "relative").
    'variant' => 'inline',
])

@if ($variant === 'overlay')
    <div
        wire:loading.flex
        @if ($target) wire:target="{{ $target }}" @endif
        {{ $attributes->class('absolute inset-0 z-10 hidden items-center justify-center rounded-[inherit] bg-white/70 backdrop-blur-[1px] dark:bg-zinc-900/70') }}
        role="status"
    >
        <x-ui.spinner class="size-6 text-accent-600 dark:text-accent-400" />
        <span class="sr-only">{{ $label }}</span>
    </div>
@else
    <div
        wire:loading.flex
        @if ($target) wire:target="{{ $target }}" @endif
        {{ $attributes->class('hidden items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400') }}
        role="status"
    >
        <x-ui.spinner class="text-accent-600 dark:text-accent-400" />
        <span>{{ $label }}</span>
    </div>
@endif
