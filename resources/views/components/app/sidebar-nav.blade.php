@php
    /** @var \App\Services\NavigationService $navigation */
    $navigation = app(\App\Services\NavigationService::class);
    $user = auth()->user();
    $sections = $user ? $navigation->sectionsFor($user) : [];
@endphp

<nav {{ $attributes->class('flex flex-col gap-6') }} aria-label="Main">
    @foreach ($sections as $section)
        <div>
            @if ($section['label'])
                <p class="mb-2 px-3 text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $section['label'] }}</p>
            @endif
            <ul class="space-y-1">
                @foreach ($section['items'] as $item)
                    @php($active = $navigation->isActive($item))
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            x-on:click="sidebarOpen = false"
                            @class([
                                'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
                                'bg-accent-50 text-accent-800 dark:bg-accent-500/10 dark:text-accent-300' => $active,
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' => ! $active,
                            ])
                        >
                            <x-ui.icon :name="$item['icon']" @class(['size-5', 'text-accent-700 dark:text-accent-400' => $active, 'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300' => ! $active]) />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
