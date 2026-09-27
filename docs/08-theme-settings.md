# Task 07 — Theme Settings (Light / Dark / System)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 07 of 18 · **Depends on:** 01–06 · **Next:** 08 — Landing Page
>
> **Stack:** Laravel 13 · Livewire · Blade · Tailwind CSS (class-based dark mode) · Alpine.js
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Let users switch between **Light** and **Dark** mode, plus **System** (follow the OS setting). The choice applies across the whole app: landing page, auth pages and authenticated pages.

## 1. Data

- `App\Enums\ThemePreference`: `light`, `dark`, `system`.
- Migration: add `theme_preference` (string, default `system`) to `users`. Cast it to the enum.
- `UserPreferenceService::setTheme(User $user, ThemePreference $theme)`.

## 2. How the theme is applied

1. **No flash of the wrong theme.** Put a small inline script in `<head>`, before the stylesheet renders, that:
   - reads the preference (server value for signed-in users, otherwise `localStorage.theme`, otherwise `system`),
   - resolves `system` with `matchMedia('(prefers-color-scheme: dark)')`,
   - adds or removes `.dark` on `<html>`.
2. For signed-in users, render their saved preference into the page (for example a `data-theme-preference` attribute on `<html>`). The **server value wins** and is copied to `localStorage`, so the choice follows the user across devices.
3. When set to `system`, listen for OS theme changes and update live.
4. If you use `wire:navigate`, re-apply the theme after each navigation (`livewire:navigated`).
5. Make the inline script compatible with a CSP nonce, for example `Vite::useCspNonce()` or `@nonce`. Task 16 may enable a CSP.

## 3. Alpine store

Create one `Alpine.store('theme', …)` with `preference`, `resolved`, `set(pref)` and `toggle()`. All toggles use this store. Do not duplicate the logic.

## 4. UI

### Header toggle (fills the Task 06 slot)

- An icon button: sun in dark mode, moon in light mode. Clicking it toggles between light and dark.
- Accessible label ("Switch to dark mode" / "Switch to light mode").
- For signed-in users, it saves through a Livewire action that validates the value against the enum and calls `UserPreferenceService`.

### Guest toggle

The same button in the guest layout (landing and auth pages). It saves to `localStorage` only.

### Settings → Appearance page

- Route: `/settings/appearance` (name `settings.appearance`). Link it from **Settings** in the user menu.
- Three selectable cards: **Light**, **Dark**, **System**, each with a small preview swatch.
- Saving shows a success toast. Validate with `UpdateAppearanceRequest` rules, and allow only enum values.
- Users can change only **their own** preference. The user ID is never taken from the request.

## 5. Visual QA

Walk through every existing page (auth pages, layout, the component preview) in both themes. Fix any missing `dark:` styles and contrast problems.

## 6. Tests

- A signed-in user can save each valid theme value. Invalid values are rejected.
- A user cannot change another user's theme (no user ID input is accepted).
- The saved preference is rendered into the layout for signed-in users.
- Guests can load pages without a stored preference.

## Done when

- [ ] Light, Dark and System work with no flash on load.
- [ ] The header toggle works for guests and signed-in users.
- [ ] The Appearance settings page saves to the database.
- [ ] Every page looks correct in both themes.
- [ ] Tests pass.
