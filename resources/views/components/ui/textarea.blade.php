@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'rows' => 4,
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

    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:outline-none focus:ring-4 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500',
            'border-red-400 focus:border-red-500 focus:ring-red-500/15 dark:border-red-500' => $hasError,
            'border-zinc-300 focus:border-accent-500 focus:ring-accent-500/15 dark:border-zinc-700' => ! $hasError,
        ]) }}
    >{{ $slot }}</textarea>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $errors->first($field) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
