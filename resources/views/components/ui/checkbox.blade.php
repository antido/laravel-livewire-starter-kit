@props([
    'label' => null,
    'description' => null,
])

@php
    $field = $attributes->wire('model')->value() ?: $attributes->get('name');
    $suffix = $attributes->get('value') !== null ? '-'.\Illuminate\Support\Str::slug((string) $attributes->get('value')) : '';
    $id = $attributes->get('id') ?? 'field-'.str_replace('.', '-', (string) ($field ?: uniqid())).$suffix;
@endphp

<label for="{{ $id }}" class="inline-flex cursor-pointer items-start gap-2.5">
    <input
        type="checkbox"
        id="{{ $id }}"
        @if ($description) aria-describedby="{{ $id }}-description" @endif
        {{ $attributes->class('mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-700 dark:accent-accent-500') }}
    >
    @if ($label || $description)
        <span class="text-sm">
            @if ($label)
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
            @endif
            @if ($description)
                <span id="{{ $id }}-description" class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
            @endif
        </span>
    @endif
</label>
