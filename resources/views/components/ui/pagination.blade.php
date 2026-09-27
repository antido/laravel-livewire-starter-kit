@props(['paginator'])

{{-- Livewire pagination: the page changes over a Livewire request, and the page number stays in the query string. --}}
@if ($paginator->hasPages())
    @php($pageName = $paginator->getPageName())
    <nav role="navigation" aria-label="Pagination" {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Showing <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1">
            <x-ui.button
                variant="secondary"
                size="sm"
                wire:click="previousPage('{{ $pageName }}')"
                :disabled="$paginator->onFirstPage()"
                aria-label="Previous page"
            >
                <x-ui.icon name="chevron-left" class="size-4" />
            </x-ui.button>

            <span class="px-2 text-sm text-zinc-600 dark:text-zinc-400" aria-current="page">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            <x-ui.button
                variant="secondary"
                size="sm"
                wire:click="nextPage('{{ $pageName }}')"
                :disabled="! $paginator->hasMorePages()"
                aria-label="Next page"
            >
                <x-ui.icon name="chevron-right" class="size-4" />
            </x-ui.button>
        </div>
    </nav>
@endif
