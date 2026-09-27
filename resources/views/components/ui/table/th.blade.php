@props([
    'sortable' => false,
    // "asc", "desc", or null when the table is not sorted by this column.
    'direction' => null,
    'align' => 'left',
])

@php
    $alignClass = ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'][$align] ?? 'text-left';
    $ariaSort = match ($direction) {
        'asc' => 'ascending',
        'desc' => 'descending',
        default => $sortable ? 'none' : null,
    };
@endphp

<th scope="col" @if ($ariaSort) aria-sort="{{ $ariaSort }}" @endif @class(['px-3 py-3 whitespace-nowrap sm:px-4', $alignClass])>
    @if ($sortable)
        <button type="button" {{ $attributes->class('group inline-flex items-center gap-1 rounded uppercase hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-accent-600 dark:hover:text-zinc-100') }}>
            {{ $slot }}
            <span @class(['text-zinc-900 dark:text-zinc-100' => $direction, 'text-zinc-300 group-hover:text-zinc-500 dark:text-zinc-600' => ! $direction])>
                <x-ui.icon :name="match ($direction) { 'asc' => 'chevron-up', 'desc' => 'chevron-down', default => 'chevrons-up-down' }" class="size-3.5" />
            </span>
        </button>
    @else
        <span {{ $attributes }}>{{ $slot }}</span>
    @endif
</th>
