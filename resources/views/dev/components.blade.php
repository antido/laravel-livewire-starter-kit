<!DOCTYPE html>
<html @include('partials.html-attributes')>
<head>
    @include('partials.head', ['title' => $title ?? null])
</head>
<body class="min-h-screen" x-data>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <x-ui.page-header title="Components" description="Every shared UI component, in the light theme (left) and the dark theme (right). Local environment only." />

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach (['light', 'dark'] as $theme)
                <section @class(['rounded-2xl p-6', 'bg-zinc-50 text-zinc-800' => $theme === 'light', 'dark bg-zinc-950 text-zinc-200' => $theme === 'dark']) aria-label="{{ ucfirst($theme) }} theme">
                    <h2 class="mb-6 text-sm font-semibold tracking-wide uppercase">{{ ucfirst($theme) }}</h2>

                    <div class="space-y-8">
                        <div>
                            <h3 class="mb-3 text-sm font-medium">Buttons</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button>Primary</x-ui.button>
                                <x-ui.button variant="secondary">Secondary</x-ui.button>
                                <x-ui.button variant="ghost">Ghost</x-ui.button>
                                <x-ui.button variant="danger">Danger</x-ui.button>
                                <x-ui.button size="sm" icon="plus">Small</x-ui.button>
                                <x-ui.button size="lg">Large</x-ui.button>
                                <x-ui.button disabled>Disabled</x-ui.button>
                                <x-ui.button href="#" variant="secondary">Link</x-ui.button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.input label="Name" id="demo-name-{{ $theme }}" placeholder="Juan Dela Cruz" hint="As it appears on your ID." />
                            <x-ui.input label="Password" type="password" id="demo-password-{{ $theme }}" />
                            <x-ui.select label="Status" id="demo-status-{{ $theme }}" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                            <x-ui.textarea label="Notes" id="demo-notes-{{ $theme }}" rows="2" />
                            <x-ui.checkbox id="demo-check-{{ $theme }}" label="Send a welcome email" description="The user gets a link to set their password." />
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge>Neutral</x-ui.badge>
                            <x-ui.badge variant="accent">Accent</x-ui.badge>
                            <x-ui.badge variant="success">Success</x-ui.badge>
                            <x-ui.badge variant="warning">Warning</x-ui.badge>
                            <x-ui.badge variant="danger">Danger</x-ui.badge>
                        </div>

                        <div class="space-y-2">
                            <x-ui.alert variant="info" title="Heads up">An informational message.</x-ui.alert>
                            <x-ui.alert variant="success">Saved successfully.</x-ui.alert>
                            <x-ui.alert variant="warning" dismissible>This one can be dismissed.</x-ui.alert>
                            <x-ui.alert variant="danger">Something went wrong.</x-ui.alert>
                        </div>

                        <x-ui.card>
                            <x-slot:header>
                                <h3 class="text-sm font-semibold">Card with header and footer</h3>
                                <x-ui.badge variant="accent">New</x-ui.badge>
                            </x-slot:header>
                            <p class="text-sm">Cards group related content.</p>
                            <x-slot:footer>
                                <x-ui.button variant="secondary" size="sm">Cancel</x-ui.button>
                                <x-ui.button size="sm">Save</x-ui.button>
                            </x-slot:footer>
                        </x-ui.card>

                        <x-ui.table>
                            <x-slot:head>
                                <x-ui.table.th sortable direction="asc">Name</x-ui.table.th>
                                <x-ui.table.th>Status</x-ui.table.th>
                                <x-ui.table.th align="right">Actions</x-ui.table.th>
                            </x-slot:head>
                            @foreach (['Maria Santos' => 'success', 'Jose Rizal' => 'neutral'] as $person => $variant)
                                <x-ui.table.tr>
                                    <x-ui.table.td>
                                        <div class="flex items-center gap-3"><x-ui.avatar :name="$person" size="sm" /> {{ $person }}</div>
                                    </x-ui.table.td>
                                    <x-ui.table.td><x-ui.badge :variant="$variant">{{ $variant === 'success' ? 'Active' : 'Inactive' }}</x-ui.badge></x-ui.table.td>
                                    <x-ui.table.td align="right"><x-ui.button variant="ghost" size="sm" icon="pencil">Edit</x-ui.button></x-ui.table.td>
                                </x-ui.table.tr>
                            @endforeach
                        </x-ui.table>

                        <x-ui.card :padding="false">
                            <x-ui.empty-state icon="search" title="No results" message="Try a different search or clear the filters.">
                                <x-ui.button variant="secondary" size="sm">Clear filters</x-ui.button>
                            </x-ui.empty-state>
                        </x-ui.card>

                        <div class="flex flex-wrap items-center gap-4">
                            <x-ui.avatar name="Juan Dela Cruz" size="sm" />
                            <x-ui.avatar name="Juan Dela Cruz" />
                            <x-ui.avatar name="Ana" size="lg" />
                            <x-ui.spinner class="text-accent-600" />
                            <x-ui.dropdown label="Demo menu" align="left">
                                <x-slot:trigger>
                                    <span class="inline-flex h-10 items-center gap-2 rounded-lg px-3 text-sm ring-1 ring-zinc-300 ring-inset dark:ring-zinc-700">Menu <x-ui.icon name="chevron-down" class="size-4" /></span>
                                </x-slot:trigger>
                                <x-ui.dropdown-item icon="settings" href="#">Settings</x-ui.dropdown-item>
                                <x-ui.dropdown-item icon="logout" danger>Log out</x-ui.dropdown-item>
                            </x-ui.dropdown>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.button variant="secondary" x-on:click="$dispatch('open-modal', 'demo-modal')">Open modal</x-ui.button>
                            <x-ui.button variant="secondary" x-on:click="$dispatch('confirm', { title: 'Delete item?', message: 'This cannot be undone.', confirmLabel: 'Delete', danger: true, action: () => $dispatch('toast', { type: 'success', message: 'Deleted (demo).' }) })">Confirm dialog</x-ui.button>
                            <x-ui.button variant="secondary" x-on:click="$dispatch('toast', { type: 'success', message: 'Saved successfully.' })">Success toast</x-ui.button>
                            <x-ui.button variant="secondary" x-on:click="$dispatch('toast', { type: 'danger', message: 'Something went wrong.' })">Error toast</x-ui.button>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <x-ui.modal name="demo-modal" title="Modal title" description="Focus stays inside; Esc or the backdrop closes it.">
        <x-ui.input label="Something" id="demo-modal-input" />
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Cancel</x-ui.button>
            <x-ui.button x-on:click="open = false">Save</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.toast />
    <x-ui.confirm-dialog />
    @livewireScripts
</body>
</html>
