# Task 06 — Application Layout (Sidebar, Header, Mobile Navigation)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 06 of 18 · **Depends on:** 01–05 · **Next:** 07 — Theme Settings
>
> **Stack:** Laravel 13 · Livewire · Blade · Tailwind CSS · Alpine.js · Spatie Laravel Permission
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Create the reusable authenticated layout and the guest layout.

## 1. Authenticated layout

```text
-----------------------------------------------------------------
| ☰  Logo / App Name              [☀/☾]  [🔔 3]  [JD ▾ User]    |
-----------------------------------------------------------------
| Sidebar                |                                      |
|                        |                                      |
| Dashboard              |            Page Content              |
| Employees              |                                      |
|                        |                                      |
| ADMINISTRATION         |                                      |
|   Users                |                                      |
|   Roles                |                                      |
|   Privileges           |                                      |
|   Audit Trails         |                                      |
|                        |                                      |
-----------------------------------------------------------------
```

### Sidebar

| Item | Route name | Required permission |
|------|------------|---------------------|
| Dashboard | `dashboard` | `dashboard.view` |
| Employees | `employees.index` | `employees.view` |
| **Administration** (group label) | — | shown only if at least one child is visible |
| Users | `admin.users.index` | `users.view` |
| Roles | `admin.roles.index` | `roles.view` |
| Privileges | `admin.permissions.index` | `permissions.view` |
| Audit Trails | `admin.audit-trails.index` | `audit_trails.view` |

- Define the menu **in one place** (for example `config/navigation.php` or a `NavigationService`), not as repeated Blade markup.
- Show an item only if the user has the permission **and** the route exists (`Route::has()`). Employees and Audit Trails are built in later tasks and must not break the layout before then.
- Highlight the active item, including child routes (`admin.users.*`).
- Use an icon plus a label for each item. The sidebar is fixed on desktop and becomes an off-canvas drawer (Alpine) on mobile, with a backdrop, Esc to close, and closing on navigation.
- **Do not** include Sales, Inventory, Suppliers, Purchases, Expenses, Payroll or Reports.

> Hiding a menu item is only a convenience. Every route is still protected by middleware and policies.

### Header (top bar)

- Left: hamburger (mobile only), plus the page title or breadcrumb from a `$title` slot.
- Right, in this order:
  1. **Theme toggle**: leave a placeholder slot; Task 07 fills it.
  2. **Notification bell**: leave a placeholder slot; Task 15 fills it.
  3. **User menu** dropdown: avatar with initials, name, email, a **Settings** link (target route added in Task 07), and **Log out** (a POST form with CSRF).

### Content area

- A `page-header` component slot, then a centred content container (for example `max-w-7xl`).
- The toast container is mounted once in the layout.
- Session flash messages are shown as toasts.

## 2. Guest layout

Used by the landing page and the auth pages. It has a minimal top bar (logo, placeholder for the theme toggle) and centred content. It supports light and dark mode.

## 3. Livewire

- Use a single layout component, for example `<x-layouts.app>`, as the default Livewire layout.
- If you use `wire:navigate`, make sure the sidebar state and the active item update correctly after navigation.

## 4. Tests

- A user with only `dashboard.view` sees Dashboard but no Administration group.
- A user with `users.view` sees Administration → Users only.
- A Super Admin sees all items whose routes exist.
- No out-of-scope module appears in the menu.
- Logout from the user menu works and requires POST with CSRF.

## Done when

- [ ] The authenticated layout has a sidebar, header, user menu and mobile drawer.
- [ ] The menu is defined in one place and respects permissions and route existence.
- [ ] Placeholder slots for the theme toggle and notification bell exist.
- [ ] The guest layout exists.
- [ ] Tests pass.
