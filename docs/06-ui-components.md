# Task 05 — Reusable UI Components

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 05 of 18 · **Depends on:** 01–04 · **Next:** 06 — Application Layout
>
> **Stack:** Laravel 13 · Livewire · Blade · Tailwind CSS · Alpine.js (bundled with Livewire)
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Build a small, consistent component library that every page will use. **Every component must support light and dark mode from the start**, using Tailwind `dark:` variants. Task 07 wires up the theme switch itself.

## 1. Design direction

- Clean and minimal: generous whitespace, subtle borders, soft rounded corners (`rounded-lg` / `rounded-xl`), light shadows or none.
- A neutral palette (for example `zinc` or `slate`) plus **one accent colour** (default: emerald). Define the accent **once** as a theme token (Tailwind theme or CSS variables) so it can be changed in one place.
- System font stack or Inter.
- Accessible: visible focus rings, labels on every input, `aria-*` where needed, and WCAG AA contrast in **both** themes.

## 2. Dark mode setup

Use the class strategy, with `.dark` on `<html>`:

- Tailwind v4: `@custom-variant dark (&:where(.dark, .dark *));` in `app.css`
- Tailwind v3: `darkMode: 'class'` in `tailwind.config.js`

## 3. Components

Create anonymous Blade components under `resources/views/components/ui/`:

| Component | Notes |
|-----------|-------|
| `button` | variants: primary, secondary, ghost, danger; sizes sm/md/lg; `wire:loading` spinner + disabled state; renders `<a>` when `href` is given |
| `input` | label, hint, error (reads `$errors`), supports `wire:model` |
| `select` | same API as input |
| `checkbox` | label + description |
| `textarea` | same API as input |
| `card` | optional header/footer slots |
| `modal` | Alpine; focus trap, closes on Esc and backdrop click, `aria-modal` |
| `confirm-dialog` | built on `modal`; title, message, confirm/cancel; danger variant |
| `alert` | info / success / warning / danger; dismissible |
| `toast` | global Alpine store; stacks in a corner; auto-dismiss; triggered from Livewire through `$this->dispatch('toast', type: 'success', message: '...')` and from session flash |
| `table` | `table`, `thead`, `th` (sortable indicator), `tr`, `td`; responsive horizontal scroll |
| `pagination` | custom Livewire pagination view matching the design |
| `badge` | neutral, accent, success, warning, danger |
| `dropdown` | Alpine; used by the header menus in Tasks 06, 07 and 15 |
| `avatar` | initials fallback |
| `spinner` / `loading` | inline + overlay variants |
| `empty-state` | icon, title, message, optional action |
| `page-header` | title, description, actions slot |
| `icon` | inline SVG icons (for example Heroicons, through `blade-ui-kit/blade-heroicons` or inline SVG). Keep one approach. |

Rules:

- No duplicated markup. Pages compose these components.
- Components output escaped content only. Never use `{!! !!}` for user data.

## 4. Component preview (local only)

Add a `/dev/components` page that renders every component in both themes. Register it **only when `app()->environment('local')`**.

## 5. Replace the temporary auth markup

Update the Task 04 auth pages to use these components.

## 6. Tests

- Blade component render tests for button, input (showing an error), badge, alert and empty-state.
- `/dev/components` returns 404 outside the `local` environment.

## Done when

- [ ] All listed components exist and support light and dark mode.
- [ ] Toasts work from Livewire events and from session flash.
- [ ] The accent colour is defined in one place.
- [ ] Auth pages use the components.
- [ ] Tests pass.
