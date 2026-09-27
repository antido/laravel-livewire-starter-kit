/*
 * Alpine is bundled with Livewire, so it is not imported here. Stores are registered on "alpine:init",
 * which Livewire fires when it starts Alpine (after this module has run).
 */

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /*
     * Theme: the one place that changes Light / Dark / System. The inline script in <head>
     * (partials/theme-script) already applied the theme before the first paint.
     * Every toggle uses $store.theme.set(...) or $store.theme.toggle().
     */
    Alpine.store('theme', {
        preference: window.appTheme?.preference ?? 'system',
        resolved: window.appTheme?.resolved ?? 'light',

        init() {
            // While following the system, react to OS theme changes straight away.
            window.appTheme?.media.addEventListener('change', () => {
                if (this.preference === 'system') {
                    this.apply();
                }
            });

            // Keep other tabs in sync.
            window.addEventListener('storage', (event) => {
                if (event.key === 'theme' && ['light', 'dark', 'system'].includes(event.newValue)) {
                    this.preference = event.newValue;
                    this.apply();
                }
            });
        },

        set(preference) {
            if (!['light', 'dark', 'system'].includes(preference)) {
                return;
            }

            this.preference = preference;

            try {
                localStorage.setItem('theme', preference);
            } catch (error) {
                // Storage can be unavailable; the theme still applies for this page.
            }

            this.apply();
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { preference } }));
        },

        toggle() {
            this.set(this.resolved === 'dark' ? 'light' : 'dark');
        },

        apply() {
            // Switch instantly: without this, every element with a colour transition would fade between themes.
            const root = document.documentElement;
            root.classList.add('theme-switching');
            this.resolved = window.appTheme?.apply(this.preference) ?? this.resolved;
            window.getComputedStyle(root).opacity;
            requestAnimationFrame(() => root.classList.remove('theme-switching'));
        },
    });

    /*
     * Toasts: short-lived feedback in a corner of the screen.
     * From Livewire: $this->dispatch('toast', type: 'success', message: 'Saved.');
     * From Alpine:   $dispatch('toast', { type: 'danger', message: 'Something went wrong.' })
     * From a redirect: session()->flash('success', '...') (rendered by the toast container).
     */
    Alpine.store('toasts', {
        items: [],
        nextId: 1,
        add({ type = 'info', message = '', timeout = 5000 } = {}) {
            if (!message) {
                return;
            }

            const id = this.nextId++;
            this.items.push({ id, type, message });

            if (timeout > 0) {
                setTimeout(() => this.remove(id), timeout);
            }
        },
        remove(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });
});

window.addEventListener('toast', (event) => {
    window.Alpine?.store('toasts')?.add(event.detail ?? {});
});

// With wire:navigate the <html> element is kept, but re-apply the theme after each visit to be safe.
document.addEventListener('livewire:navigated', () => {
    window.Alpine?.store('theme')?.apply();
});
