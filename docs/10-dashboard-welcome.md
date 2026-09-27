# Task 09 — Dashboard (Simple Welcome Page)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 09 of 18 · **Depends on:** 01–08 · **Next:** 10 — User Management
>
> **Stack:** Laravel 13 · Livewire · Blade · Tailwind CSS · shared UI components · authenticated layout
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Replace the placeholder dashboard with a **simple, clean welcome page** at `/dashboard`. There are **no KPI cards, charts or analytics**.

## 1. Content

```text
┌──────────────────────────────────────────────────────────┐
│                                                          │
│   Good morning, Juan 👋                                  │  ← greeting by time of day (APP_TIMEZONE)
│   Sunday, 27 September 2026 · [Administrator]            │  ← date + role badge(s)
│                                                          │
│   Welcome to Laravel Starter Kit. Use the menu on        │
│   the left to get started.                               │
│                                                          │
│   ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │  ← quick links (only permitted ones)
│   │ Employees   │ │ Users       │ │ Settings    │        │
│   │ short line →│ │ short line →│ │ short line →│        │
│   └─────────────┘ └─────────────┘ └─────────────┘        │
│                                                          │
│   You have 2 unread notifications.  View →               │  ← only if > 0 (after Task 15)
│                                                          │
└──────────────────────────────────────────────────────────┘
```

- **Greeting:** "Good morning / afternoon / evening, {first name}". Use the app timezone.
- **Quick links:** at most 4 small cards, shown only when the user has the permission **and** the route exists (Employees → `employees.view`, Users → `users.view`, Audit Trails → `audit_trails.view`, Settings → always). Reuse the navigation definition from Task 06. Do not create a second list.
- **Unread notifications line:** hide it until Task 15 exists (guard it with `Route::has('notifications.index')`). Show it only when the count is above 0.
- Lots of whitespace. Works in light and dark mode and on mobile.

## 2. Implementation

- Livewire component: `App\Livewire\Dashboard\Dashboard`. Keep it thin, with no business logic.
- Route `/dashboard` (name `dashboard`), protected by `auth`, `verified` (when enabled), `active` and `permission:dashboard.view`.
- After login, redirect here.
- No `DashboardService` or `DashboardResource` is needed at this stage.

## 3. Tests

- A signed-in user with `dashboard.view` sees their name in the greeting.
- A guest is redirected to login.
- A user without `dashboard.view` gets 403.
- Quick links show only permitted items. A Staff user sees only Settings.
- The page contains no KPI or analytics placeholders.

## Done when

- [ ] `/dashboard` shows the minimal welcome page.
- [ ] Quick links respect permissions.
- [ ] Tests pass.
