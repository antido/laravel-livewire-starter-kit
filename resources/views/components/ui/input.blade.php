@props([
    'label' => null,
    'type' => 'text',
    'hint' => null,
    // The validation key to show errors for. Defaults to the wire:model (or name) of the input.
    'error' => null,
])

@php
    $field = $error ?? ($attributes->wire('model')->value() ?: $attributes->get('name'));
    $id = $attributes->get('id') ?? ($field ? 'field-'.str_replace('.', '-', $field) : null);
    $hasError = $field && isset($errors) && $errors->has($field);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);
    // Password fields get an eye button to show or hide what was typed.
    $isPassword = $type === 'password';
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
    @endif

    <div @class(['relative' => $isPassword]) @if ($isPassword) x-data="{ show: false }" @endif>
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            @if ($isPassword) x-bind:type="show ? 'text' : 'password'" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'block h-10 w-full rounded-lg border bg-white pl-3 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500',
                'read-only:bg-zinc-50 read-only:text-zinc-500 disabled:cursor-not-allowed disabled:opacity-60 dark:read-only:bg-zinc-800/50 dark:read-only:text-zinc-400',
                'focus:outline-none focus:ring-4',
                'pr-10' => $isPassword,
                'pr-3' => ! $isPassword,
                'border-red-400 focus:border-red-500 focus:ring-red-500/15 dark:border-red-500' => $hasError,
                'border-zinc-300 focus:border-accent-500 focus:ring-accent-500/15 dark:border-zinc-700' => ! $hasError,
            ]) }}
        >
        @if ($isPassword)
            <button
                type="button"
                x-on:click="show = ! show"
                x-bind:aria-pressed="show.toString()"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                aria-label="Show password"
                aria-controls="{{ $id }}"
                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-zinc-400 transition hover:text-zinc-700 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent-600 dark:text-zinc-500 dark:hover:text-zinc-200"
            >
                <x-ui.icon name="eye" class="size-4" x-show="! show" />
                <x-ui.icon name="eye-off" class="size-4" x-show="show" x-cloak />
            </button>
        @endif
    </div>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $errors->first($field) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
