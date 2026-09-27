@php
    /** @var list<array{label: string, url: string|null}> $crumbs */
    $crumbs = app(\App\Services\BreadcrumbService::class)->for(request()->route(), auth()->user());
@endphp

@if (count($crumbs) > 0)
    <nav aria-label="Breadcrumb" {{ $attributes->class('mb-4 min-w-0') }} data-test="breadcrumbs">
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
            @foreach ($crumbs as $crumb)
                <li class="flex min-w-0 items-center gap-1.5">
                    @if (! $loop->first)
                        <x-ui.icon name="chevron-right" class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" />
                    @endif
                    @if ($loop->last)
                        <span aria-current="page" class="max-w-[16rem] truncate font-medium text-zinc-900 sm:max-w-md dark:text-zinc-100">{{ $crumb['label'] }}</span>
                    @elseif ($crumb['url'])
                        <a href="{{ $crumb['url'] }}" class="max-w-[12rem] truncate rounded hover:text-zinc-900 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 sm:max-w-xs dark:hover:text-zinc-100">{{ $crumb['label'] }}</a>
                    @else
                        <span class="max-w-[12rem] truncate sm:max-w-xs">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
