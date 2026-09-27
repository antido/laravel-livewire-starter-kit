<div
    x-on:theme-saved.window="$store.theme.set($event.detail.theme)"
    x-on:theme-changed.window="$wire.theme = $event.detail.preference"
>
    <x-ui.page-header title="Appearance" description="Choose how the app looks to you. Your choice is saved to your account and follows you to other devices." />

    <x-ui.card class="max-w-3xl">
        <form wire:submit="save">
            <fieldset>
                <legend class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Theme</legend>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($themes as $option)
                        <label
                            @class([
                                'group relative flex cursor-pointer flex-col rounded-xl border p-3 transition focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-600',
                                'border-accent-600 ring-1 ring-accent-600 dark:border-accent-500 dark:ring-accent-500' => $theme === $option->value,
                                'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' => $theme !== $option->value,
                            ])
                        >
                            <input type="radio" name="theme" value="{{ $option->value }}" wire:model.live="theme" class="sr-only" data-test="theme-{{ $option->value }}">

                            {{-- Preview swatch --}}
                            <span class="block h-20 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700" aria-hidden="true">
                                @if ($option === \App\Enums\ThemePreference::System)
                                    <span class="flex h-full">
                                        <span class="flex h-full w-1/2 flex-col gap-1.5 bg-zinc-50 p-2"><span class="h-2 w-8 rounded bg-zinc-300"></span><span class="h-2 w-12 rounded bg-zinc-200"></span><span class="mt-auto h-3 w-6 rounded bg-accent-600"></span></span>
                                        <span class="flex h-full w-1/2 flex-col gap-1.5 bg-zinc-900 p-2"><span class="h-2 w-8 rounded bg-zinc-600"></span><span class="h-2 w-12 rounded bg-zinc-700"></span><span class="mt-auto h-3 w-6 rounded bg-accent-500"></span></span>
                                    </span>
                                @else
                                    <span @class(['flex h-full flex-col gap-1.5 p-2', 'bg-zinc-50' => $option === \App\Enums\ThemePreference::Light, 'bg-zinc-900' => $option === \App\Enums\ThemePreference::Dark])>
                                        <span @class(['h-2 w-10 rounded', 'bg-zinc-300' => $option === \App\Enums\ThemePreference::Light, 'bg-zinc-600' => $option === \App\Enums\ThemePreference::Dark])></span>
                                        <span @class(['h-2 w-16 rounded', 'bg-zinc-200' => $option === \App\Enums\ThemePreference::Light, 'bg-zinc-700' => $option === \App\Enums\ThemePreference::Dark])></span>
                                        <span @class(['mt-auto h-3 w-8 rounded', 'bg-accent-600' => $option === \App\Enums\ThemePreference::Light, 'bg-accent-500' => $option === \App\Enums\ThemePreference::Dark])></span>
                                    </span>
                                @endif
                            </span>

                            <span class="mt-3 flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <x-ui.icon :name="$option->icon()" class="size-4 text-zinc-500 dark:text-zinc-400" />
                                {{ $option->label() }}
                                @if ($theme === $option->value)
                                    <x-ui.icon name="check" class="ml-auto size-4 text-accent-700 dark:text-accent-400" />
                                @endif
                            </span>
                            <span class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $option->description() }}</span>
                        </label>
                    @endforeach
                </div>

                @error('theme')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="mt-6 flex justify-end">
                <x-ui.button type="submit" loading="save">Save</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
