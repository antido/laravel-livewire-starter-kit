@props(['value'])

{{-- One audited value, always escaped. Lists become comma-separated; other structures are shown as formatted JSON. --}}
@if ($value === null || $value === '' || $value === [])
    <span class="text-zinc-400 dark:text-zinc-500">—</span>
@elseif (is_bool($value))
    <span>{{ $value ? 'Yes' : 'No' }}</span>
@elseif (is_array($value) && array_is_list($value) && collect($value)->every(fn ($item) => is_scalar($item)))
    <span class="break-words">{{ implode(', ', $value) }}</span>
@elseif (is_array($value))
    <pre class="max-w-full overflow-x-auto rounded bg-zinc-50 p-2 font-mono text-xs whitespace-pre-wrap dark:bg-zinc-800/60">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
@else
    <span class="break-words">{{ (string) $value }}</span>
@endif
