@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    // Shown as an empty first option when set.
    'placeholder' => null,
    // value => label pairs; more <option>s can go in the slot.
    'options' => [],
])

@php
    $field = $error ?? ($attributes->wire('model')->value() ?: $attributes->get('name'));
    $id = $attributes->get('id') ?? ($field ? 'field-'.str_replace('.', '-', $field) : null);
    $hasError = $field && isset($errors) && $errors->has($field);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
    @endif

    <select
        id="{{ $id }}"
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block h-10 w-full rounded-lg border bg-white pr-8 pl-3 text-sm text-zinc-900 shadow-sm transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-900 dark:text-zinc-100',
            'border-red-400 focus:border-red-500 focus:ring-red-500/15 dark:border-red-500' => $hasError,
            'border-zinc-300 focus:border-accent-500 focus:ring-accent-500/15 dark:border-zinc-700' => ! $hasError,
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $errors->first($field) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
