# Starter Kit

A clean foundation for business apps built with Laravel 13, Livewire 4 and Tailwind CSS 4. It ships the groundwork most apps need, done properly: secure sign-in, roles and privileges, employee records, audit trails, in-app notifications, and Light/Dark/System themes.

**Included**

- Authentication (session-based, with Sanctum): log in with remember-me, sign up (can be turned off), log out, forgot and reset password, email verification, confirm password, and signing out deactivated users
- A minimal public landing page and a simple welcome dashboard
- An app layout with a sidebar, a header (theme toggle, notification bell, user menu) and a mobile drawer
- Theme settings: Light, Dark or System, saved per user
- Employees, each optionally linked to one login account
- Administration: Users, Roles, Privileges (permissions) and Audit Trails
- In-app database notifications
- A security baseline, 247 tests, Pint and Larastan

**Not included:** Sales, Inventory, Suppliers, Purchases, Expenses, Payroll and Reports. There are no permissions, routes, menu items or tables for them. Add each as its own module when you need it (see [Adding a module](#adding-a-module)).

## Requirements

- PHP 8.4+ with the `pdo_mysql`, `mbstring`, `openssl` and `fileinfo` extensions
- Composer 2
- Node.js 20+ and npm
- MySQL 8

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Then edit `.env`:

| Setting | What to set |
|---|---|
| `APP_NAME`, `APP_URL`, `APP_TIMEZONE` | Your app's name, URL and timezone (default `Asia/Manila`) |
| `DB_*` | Your MySQL database and credentials |
| `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD` | The first Super Admin, created by the seeder |
| `MAIL_*` | A real mailer for verification and password-reset emails. `MAIL_MAILER=log` writes them to `storage/logs` |

Create the tables, seed the roles, permissions and Super Admin, and build the assets:

```bash
php artisan migrate --seed
npm run build
```

Open the app with `composer dev` (see below) or any local server, then log in as the Super Admin.

> **No credentials in the repository.** `.env` is git-ignored and `.env.example` holds placeholders only. You can remove `SUPER_ADMIN_PASSWORD` from `.env` once the account exists.

### Creating a Super Admin interactively

Instead of the `SUPER_ADMIN_*` variables, run:

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan app:create-super-admin
```

It asks for the name, email and password (the password is hidden as you type). If the variables are missing, `php artisan db:seed` stops with a message that tells you which ones to set.

## Development

```bash
composer dev
```

This runs the web server, the queue listener (notifications are queued), the log viewer, the scheduler (which prunes old audit entries) and Vite with hot reload, all together.

| Command | What it does |
|---|---|
| `composer test` | The test suite (PHPUnit, in-memory SQLite) |
| `composer lint` | Checks formatting (Pint) |
| `composer format` | Fixes formatting |
| `composer analyse` | Static analysis (Larastan, level 6) |
| `npm run build` | Production assets |

A preview of every UI component in both themes is at `/dev/components`. It exists only when `APP_ENV=local`.

## Authentication

- **Sign-up** is controlled by `REGISTRATION_ENABLED`. When it is `false`, the sign-up page returns 404 and its links disappear.
- **New accounts** get the role in `DEFAULT_REGISTRATION_ROLE` (default `User`). The role is never taken from the request, and the setting can never be `Super Admin`.
- **Passwords** need at least 8 characters. In production they must also mix upper and lower case, include a number, and not appear in known data breaches.
- **Login** allows 5 failed attempts per minute for each email and IP address. The error never reveals whether an account exists.
- **Inactive accounts** cannot log in, and an open session ends on the next request after deactivation.
- **Email verification** is required for self-registered users. Accounts made by an administrator, or by `app:create-super-admin`, count as verified.
- **Password reset** always answers with the same message, whether or not the email has an account.

## Roles and privileges

Permissions use the `module.action` format. The system permissions are defined once, in `App\Enums\PermissionName`. Roles are in `App\Enums\RoleName`.

| Permissions | Super Admin | Owner | Administrator | User |
|---|:-:|:-:|:-:|:-:|
| `dashboard.view` | all (bypass) | ✓ | ✓ | ✓ |
| `users.*` | all (bypass) | ✓ | ✓ | – |
| `roles.*` | all (bypass) | ✓ | view only | – |
| `permissions.*` | all (bypass) | ✓ | view only | – |
| `employees.*` | all (bypass) | ✓ | ✓ | – |
| `audit_trails.view` | all (bypass) | ✓ | ✓ | – |

The Super Admin passes every check through `Gate::before()`. The seeder (`RolePermissionSeeder`) is safe to run again.

These rules are enforced on the server, in the services and policies. Hiding a button is never the only protection.

1. Only a Super Admin can give or remove the Super Admin role, or edit or delete a Super Admin.
2. Anyone else can assign only roles whose permissions they hold themselves, and can grant only permissions they hold themselves (`RoleService::canAssign()` and `canGrant()`).
3. Users cannot change their own roles or status, and cannot delete themselves.
4. The last active Super Admin cannot be deactivated, demoted or deleted.
5. System roles cannot be deleted or renamed, and the Super Admin role's permissions cannot be edited. System permissions cannot be renamed or deleted.
6. A role that is still assigned to users, or a custom privilege still used by a role, cannot be deleted. Remove it from them first.

Every route checks its permission through middleware, and every Livewire component authorizes again on each action.

## Employees and user accounts

An employee record can exist without a login, and a user can exist without an employee record. When creating or editing an employee you can:

- **create a new account.** This needs `users.create`. The account's name is the employee's full name, and its roles follow the rules above.
- **link an existing user** who has no employee yet. This needs permission to update that user.
- **unlink the account.** The user account is kept.

Setting an employee to *Resigned* or *Terminated* offers to deactivate the linked account as well. Employee numbers (`EMP-00001`) are generated by the server. Archived employees can be restored.

## Audit trail

Sign-ins, sign-outs, failed sign-ins, password resets, and every change to users, roles, privileges and employees are recorded. Each entry stores who acted, their IP address and browser, and the before/after values. For updates, only the fields that changed are stored.

- **Immutable:** the `AuditLog` model refuses updates and deletes, and the viewer has no edit or delete actions.
- **Redaction:** passwords, tokens and secrets are removed before an entry is stored. The key list is in `config/audit.php`.
- **Retention:** set `AUDIT_RETENTION_DAYS` to prune older entries daily through the scheduler. Leave it empty to keep everything. In production, run the scheduler with cron (`* * * * * php artisan schedule:run`).

The viewer is at **Administration → Audit Trails** and needs `audit_trails.view`.

## Notifications

Notifications are stored in the `notifications` table, shown in the header bell, and listed at `/notifications`. They are queued after the database transaction commits, so run a queue worker in production (`php artisan queue:work`). With `QUEUE_CONNECTION=sync` they are sent immediately.

Sent automatically:

- a welcome message, and a "new user signed up" notice to user managers
- a "your roles changed" notice
- a "your password was changed by an administrator" notice
- an "account linked to an employee" notice

Links only ever point to internal paths, and users can only see or change their own notifications.

## Themes

Each user chooses Light, Dark or System in **Settings → Appearance** or with the header toggle. The choice is stored in `users.theme_preference` and follows them to other devices. Guests' choices are kept in the browser (`localStorage`). A small inline script applies the theme before the page is drawn, so the wrong theme never flashes.

The accent colour is defined once, as `--color-accent-*` in `resources/css/app.css`.

## Security notes

For production, set:

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true   # HTTPS only
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

- **Security headers** (`App\Http\Middleware\SecurityHeaders`, `config/security.php`) are on by default in production, or when `SECURITY_HEADERS=true`. They include a Content-Security-Policy with a per-request nonce, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` and `Permissions-Policy`. The policy allows two documented exceptions:
  - `script-src 'unsafe-eval'`, because Alpine, which Livewire bundles, evaluates the inline `x-on`/`x-data` expressions;
  - inline styles, which Alpine and Livewire use for showing and hiding elements.
- **HSTS:** set `SECURITY_HSTS=true` only once the whole site uses HTTPS.
- **Rate limits:**
  - log in, sign up, password reset and email verification are rate-limited;
  - so is the Livewire request endpoint, which also serves the notification polling (300 requests per minute per user or IP address).
- **Error pages** (403, 404, 419, 429, 500, 503) never show error details.
- **Dependency audits:** run `composer audit` and `npm audit` regularly. Both were clean when this kit was built.

## Testing

```bash
composer test
```

The suite uses PHPUnit with an in-memory SQLite database, so it never touches your data. The tests cover:

- authentication and throttling
- authorization: route middleware, policies, and direct Livewire calls that bypass the UI
- privilege-escalation rules
- users, roles, privileges and employees
- audit entries and redaction
- notifications, including protection against reading others' notifications and against open redirects
- themes, the layout and dashboard, and the landing page
- security headers and error pages

No code-coverage driver (Xdebug or PCOV) was installed when this kit was built, so there is no coverage report. With one installed, run `php artisan test --coverage`.

## Adding a module

A business module (for example Inventory) adds:

1. its permissions to `App\Enums\PermissionName`, plus any role defaults in `RoleName::defaultPermissions()`, then re-run `RolePermissionSeeder`;
2. its menu item to `config/navigation.php`;
3. routes with `permission:` middleware, a policy, a service, form requests and Livewire components following the existing modules;
4. the `Auditable` trait on its models, and feature tests.
