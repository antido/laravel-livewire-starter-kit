{{--
    Applies the theme before the first paint, so there is no flash of the wrong theme. The signed-in user's
    saved preference (data-theme-preference on <html>) wins and is copied to localStorage; guests use
    localStorage; the fallback is "system". The Alpine theme store (resources/js/app.js) takes over after load.
--}}
<script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif>
    (() => {
        const root = document.documentElement;
        const valid = ['light', 'dark', 'system'];
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        let preference = valid.includes(root.dataset.themePreference) ? root.dataset.themePreference : null;

        try {
            if (preference) {
                localStorage.setItem('theme', preference);
            } else {
                preference = localStorage.getItem('theme');
            }
        } catch (error) {
            // Storage can be unavailable (private mode, blocked cookies); the theme still applies for this page.
        }

        if (!valid.includes(preference)) {
            preference = 'system';
        }

        const apply = (value) => {
            const dark = value === 'dark' || (value === 'system' && media.matches);
            root.classList.toggle('dark', dark);
            return dark ? 'dark' : 'light';
        };

        window.appTheme = { preference, media, apply, resolved: apply(preference) };
    })();
</script>
