@props(['align' => 'left'])

<td {{ $attributes->class(['px-3 py-3 align-middle sm:px-4', ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'][$align] ?? 'text-left']) }}>
    {{ $slot }}
</td>
