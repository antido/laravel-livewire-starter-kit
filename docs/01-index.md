# Laravel Starter Kit — Project Setup Prompts

This folder splits the project setup prompt into small, ordered tasks. Give your AI coding assistant **one file per prompt**, in order. Before you move on, check that task's **Done when** list and make sure the tests pass.

Every task file stands on its own. For Task 03 onward, also paste `02-architecture-conventions.md` (or keep it in the assistant's context) so the conventions stay consistent.

---

## What the starter kit includes

- Authentication: Sanctum, Livewire pages, password reset, email verification
- A clean, minimal public landing page
- A simple welcome dashboard
- An authenticated layout with a sidebar, a header, a notification bell, a theme toggle and a user menu
- Theme settings: Light, Dark or System
- Employees, each of which can be linked to a user account
- Administration: Users, Roles, Privileges and Audit Trails
- In-app database notifications
- A security baseline, tests, formatting and static analysis

## Out of scope

The following modules are **not** part of this starter kit:

> Sales · Inventory · Suppliers · Purchases · Expenses · Payroll · Reports

Do not create anything for them: no permissions, routes, menu items, tables, placeholder cards or landing-page copy. They can be built later on top of this foundation.

---

## Build order

| # | File | What it delivers |
|---|------|------------------|
| 01 | `01-project-setup.md` | Laravel project, stack install, environment, Git hygiene |
| 02 | `02-architecture-conventions.md` | Folder structure, coding conventions, Pint and Larastan |
| 03 | `03-roles-and-permissions.md` | Spatie Permission, roles, permissions, seeders, Super Admin |
| 04 | `04-authentication.md` | Login, signup, reset, verification, inactive-user handling |
| 05 | `05-ui-components.md` | Reusable Blade/Livewire UI components (dark-mode ready) |
| 06 | `06-application-layout.md` | Authenticated layout, sidebar, header, mobile navigation |
| 07 | `07-theme-settings.md` | Light/Dark/System mode, header toggle, Appearance settings |
| 08 | `08-landing-page.md` | Clean, minimal public landing page |
| 09 | `09-dashboard-welcome.md` | Simple welcome dashboard |
| 10 | `10-user-management.md` | `/admin/users` |
| 11 | `11-role-management.md` | `/admin/roles` |
| 12 | `12-permission-management.md` | `/admin/permissions` (Privileges) |
| 13 | `13-audit-trails.md` | Audit logging plus the `/admin/audit-trails` viewer |
| 14 | `14-employees.md` | `/employees`, linked to the User module |
| 15 | `15-notifications.md` | Header bell, dropdown, `/notifications` page |
| 16 | `16-security-hardening.md` | Security review and hardening pass |
| 17 | `17-testing.md` | Test-suite completion and coverage review |
| 18 | `18-readme-and-final-verification.md` | README, `.env.example`, Definition of Done |

```text
Project Setup → Conventions → Roles & Permissions → Authentication
      → UI Components → Layout → Theme → Landing Page → Dashboard
      → Users → Roles → Privileges → Audit Trails → Employees
      → Notifications → Security Hardening → Testing → Final Verification
```

---

## Final sidebar

```text
Dashboard
Employees

Administration
  Users
  Roles
  Privileges
  Audit Trails
```

Settings (Appearance) and Log out live in the **user menu** in the header, not in the sidebar.

---

## How to use these files

1. Start with `01-project-setup.md`. Paste the whole file as your prompt.
2. After each task, run:
   ```bash
   php artisan test
   ./vendor/bin/pint --test
   ./vendor/bin/phpstan analyse
   ```
3. Tick off the task's **Done when** checklist before you start the next one.
4. If the assistant starts building something that belongs to a later task, stop it and point it back to the current file.
5. Commit after each task so each step is easy to review and roll back.

## Global rules (apply to every task)

- Enforce authorization on the backend: in routes, policies and Livewire actions. Hiding buttons in the frontend is never enough.
- Persist only validated data. Never use `$request->all()` for create or update.
- Never commit secrets, log passwords or expose password hashes or tokens.
- Keep Livewire components thin. Business logic goes in Services.
- Use Enums instead of magic strings.
- Every new feature comes with feature tests.
- Every new view supports both light and dark mode.
