<div>
    <x-ui.page-header title="Audit Trails" description="A read-only record of sign-ins and changes: who did what, and when." />

    <div class="mb-4 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_12rem_12rem_12rem]">
            <x-ui.search-input wire:model.live.debounce.400ms="search" id="audit-search" label="Search descriptions" placeholder="Search descriptions" class="sm:col-span-2 xl:col-span-1" />
            <x-ui.select wire:model.live="user" id="audit-user" aria-label="Filter by user">
                <option value="">All users</option>
                <option value="system">System</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select wire:model.live="event" id="audit-event" aria-label="Filter by event" placeholder="All events" :options="$events" />
            <x-ui.select wire:model.live="subject" id="audit-subject" aria-label="Filter by subject type" placeholder="All subjects" :options="$subjectTypes" />
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="grid w-full grid-cols-2 gap-3 sm:w-auto">
                <div>
                    <label for="audit-from" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">From</label>
                    <x-ui.input type="date" wire:model.live="from" id="audit-from" :max="now()->format('Y-m-d')" class="sm:w-44" />
                </div>
                <div>
                    <label for="audit-to" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">To</label>
                    <x-ui.input type="date" wire:model.live="to" id="audit-to" :max="now()->format('Y-m-d')" class="sm:w-44" />
                </div>
            </div>
            @if ($filtered)
                <x-ui.button variant="ghost" icon="x" wire:click="clearFilters">Clear filters</x-ui.button>
            @endif
        </div>
    </div>

    <div class="relative">
        <x-ui.loading variant="overlay" target="search,user,event,subject,from,to,clearFilters,previousPage,nextPage" />

        <x-ui.table>
            <x-slot:head>
                <x-ui.table.th>Date / time</x-ui.table.th>
                <x-ui.table.th>User</x-ui.table.th>
                <x-ui.table.th>Event</x-ui.table.th>
                <x-ui.table.th class="hidden lg:table-cell">Subject</x-ui.table.th>
                <x-ui.table.th class="hidden md:table-cell">Description</x-ui.table.th>
                <x-ui.table.th class="hidden xl:table-cell">IP address</x-ui.table.th>
                <x-ui.table.th align="right"><span class="sr-only">Details</span></x-ui.table.th>
            </x-slot:head>

            @forelse ($entries as $entry)
                <x-ui.table.tr wire:key="audit-{{ $entry->id }}" class="cursor-pointer" wire:click="show({{ $entry->id }})">
                    <x-ui.table.td class="whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                        <span class="block">{{ $entry->created_at?->format('M j, Y') }}</span>
                        <span class="block text-xs">{{ $entry->created_at?->format('g:i:s A') }}</span>
                    </x-ui.table.td>
                    <x-ui.table.td>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->actorName() }}</span>
                        <p class="mt-0.5 text-xs text-zinc-500 md:hidden dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($entry->description, 80) }}</p>
                    </x-ui.table.td>
                    <x-ui.table.td><x-ui.badge :variant="$entry->event->badge()">{{ $entry->event->label() }}</x-ui.badge></x-ui.table.td>
                    <x-ui.table.td class="hidden text-zinc-600 lg:table-cell dark:text-zinc-400">{{ $entry->subjectLabel() ?? '—' }}</x-ui.table.td>
                    <x-ui.table.td class="hidden max-w-sm md:table-cell"><span class="line-clamp-2">{{ $entry->description }}</span></x-ui.table.td>
                    <x-ui.table.td class="hidden font-mono text-xs text-zinc-500 xl:table-cell dark:text-zinc-400">{{ $entry->ip_address ?? '—' }}</x-ui.table.td>
                    <x-ui.table.td align="right">
                        <x-ui.icon-button icon="eye" :label="'View entry '.$entry->id" wire:click.stop="show({{ $entry->id }})" />
                    </x-ui.table.td>
                </x-ui.table.tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state icon="audit" title="No entries found" :message="$filtered ? 'Try different filters.' : 'Activity will appear here as people use the app.'">
                            @if ($filtered)
                                <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">Clear filters</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <x-ui.pagination :paginator="$entries" class="mt-4" />

    <x-ui.modal wire:model="showDetail" title="Audit entry" max-width="3xl">
        @if ($selected)
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2" data-test="audit-detail">
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Date / time</dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $selected->created_at?->format('l, F j, Y g:i:s A') }} <span class="text-zinc-500">({{ config('app.timezone') }})</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Event</dt>
                    <dd class="mt-1"><x-ui.badge :variant="$selected->event->badge()">{{ $selected->event->label() }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">User</dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                        {{ $selected->actorName() }}
                        @if ($selected->user?->email ?? $selected->user_email)
                            <span class="block text-xs break-all text-zinc-500 dark:text-zinc-400">{{ $selected->user?->email ?? $selected->user_email }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Subject</dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $selected->subjectLabel() ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">Description</dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $selected->description }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">IP address</dt>
                    <dd class="mt-1 font-mono text-xs text-zinc-900 dark:text-zinc-100">{{ $selected->ip_address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 uppercase dark:text-zinc-400">User agent</dt>
                    <dd class="mt-1 text-xs break-words text-zinc-600 dark:text-zinc-400">{{ $selected->user_agent ?? '—' }}</dd>
                </div>
            </dl>

            @php
                $old = $selected->old_values ?? [];
                $new = $selected->new_values ?? [];
                $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
            @endphp

            @if ($keys !== [])
                <h3 class="mt-6 mb-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Changes</h3>
                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800" data-test="audit-changes">
                        <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/50 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-3 py-2">Field</th>
                                <th scope="col" class="px-3 py-2">Before</th>
                                <th scope="col" class="px-3 py-2">After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($keys as $key)
                                @php($changed = ($old[$key] ?? null) !== ($new[$key] ?? null) && array_key_exists($key, $old) && array_key_exists($key, $new))
                                <tr @class(['bg-amber-50/70 dark:bg-amber-500/10' => $changed]) @if ($changed) data-changed="true" @endif>
                                    <th scope="row" class="px-3 py-2 text-left align-top font-mono text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $key }}
                                        @if ($changed)
                                            <span class="sr-only">(changed)</span>
                                        @endif
                                    </th>
                                    <td class="px-3 py-2 align-top text-zinc-600 dark:text-zinc-400"><x-audit.value :value="$old[$key] ?? null" /></td>
                                    <td class="px-3 py-2 align-top text-zinc-900 dark:text-zinc-100"><x-audit.value :value="$new[$key] ?? null" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Close</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
