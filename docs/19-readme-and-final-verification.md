# Task 18 — README, Environment & Final Verification

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 18 of 18 · **Depends on:** 01–17
>
> Final documentation and verification. Do not add new features.
<!-- standalone:end -->

## 1. README.md

Write a complete `README.md` that includes:

- Project overview: what the starter kit includes, and that Sales, Inventory, Suppliers, Purchases, Expenses, Payroll and Reports are **not** included
- Requirements (PHP 8.4+, Composer, Node, MySQL)
- Installation and environment configuration
- Database setup, migration and seeding (including the Super Admin env variables and `app:create-super-admin`)
- Authentication setup (registration toggle, default role, mail for verification and reset)
- Spatie Permission setup, the default role → permission table, and escalation rules
- Employees ↔ Users linking, briefly explained
- Audit trail behaviour and redaction, plus the optional retention setting
- Notifications (database channel, queue note)
- Theme settings (Light/Dark/System, where preferences are stored)
- Running the dev server and queue worker
- Running tests, formatting and static analysis
- Security notes (production `.env` values, headers/CSP, dependency audits)

Example quick start (adjust to the final configuration):

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# set DB_* and SUPER_ADMIN_* in .env
php artisan migrate --seed
npm run build
php artisan serve
```

## 2. `.env.example` final check

Confirm it contains placeholders for `APP_*` (including `APP_TIMEZONE`), `DB_*`, `SESSION_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `MAIL_*`, `REGISTRATION_ENABLED`, `DEFAULT_REGISTRATION_ROLE`, `SUPER_ADMIN_*` and `AUDIT_RETENTION_DAYS`, **with no real credentials**.

## 3. Automated verification

```bash
php artisan migrate:fresh --seed
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
composer audit
npm audit
npm run build
```

## 4. Manual verification

1. A guest can open the landing page in light and dark mode, on mobile and desktop.
2. A guest can open Log in and Sign up (Sign up is hidden when registration is disabled).
3. A guest cannot open the Dashboard, Employees, Notifications, Settings or any `/admin/*` page.
4. A registered user can log in and sees the welcome dashboard.
5. An inactive user cannot log in, and an active session ends after deactivation.
6. The theme toggle and Settings → Appearance persist across reloads and devices.
7. The notification bell shows the unread count. Mark read, mark all and view all work.
8. A Cashier or Staff user sees only Dashboard and cannot open Employees or Administration pages.
9. Staff cannot assign themselves Administrator or Super Admin.
10. Only authorized users can manage users, roles, privileges and employees.
11. An employee can be created with a new account, linked to an existing user, and unlinked.
12. The Audit Trails page shows changes with before/after values and no passwords.
13. Direct route requests and direct Livewire actions cannot bypass authorization.
14. Passwords are never exposed.
15. `.env` is not committed.
16. The app runs correctly with `APP_DEBUG=false`.
17. No Sales, Inventory, Suppliers, Purchases, Expenses, Payroll or Reports items exist anywhere.

## 5. Definition of Done

- [ ] Laravel project installed and configured, with a working MySQL connection
- [ ] Sanctum session authentication: login, signup, logout, password reset, verification
- [ ] Inactive-user handling and login rate limiting
- [ ] Clean, minimal landing page
- [ ] Simple welcome dashboard
- [ ] Authenticated layout with sidebar (Dashboard, Employees, Administration → Users, Roles, Privileges, Audit Trails), header and mobile navigation
- [ ] Light/Dark/System theme with header toggle and Appearance settings
- [ ] Notification bell, dropdown and notifications page
- [ ] Livewire, Tailwind and reusable UI components
- [ ] Spatie Permission installed, with roles, permissions and Super Admin seeded
- [ ] User, Role and Privilege management UIs
- [ ] Employees module linked to Users
- [ ] Immutable audit trails with redaction and viewer
- [ ] Backend authorization enforced (middleware, policies, Livewire actions)
- [ ] Services, Form Requests, Resources, Enums, Traits and Collections used appropriately
- [ ] Unit and feature tests, including authorization-bypass tests
- [ ] Pint, static analysis and tests all pass
- [ ] Dependency audits done
- [ ] `.env` excluded from Git, no secrets committed
- [ ] README contains complete setup instructions

## 6. What comes next (not part of this kit)

Once everything above passes, business modules (for example Sales, Inventory, Suppliers, Purchases, Expenses, Payroll, Reports) can be added as separate tasks. Each one adds its own permissions to `PermissionName`, its menu items to the navigation config, and its own tests, without restructuring the foundation.
