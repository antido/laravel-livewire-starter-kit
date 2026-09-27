# Task 03 — Roles, Permissions & Seeders

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 03 of 18 · **Depends on:** 01–02 · **Next:** 04 — Authentication
>
> **Stack:** Laravel 13 · PHP 8.4+ · MySQL · Sanctum · Livewire · Tailwind · Spatie Laravel Permission · PHPUnit
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Install Spatie Laravel Permission, define the roles and permissions, create the seeders and the Super Admin, and set the authorization rules the rest of the app relies on.

## 1. Install Spatie Laravel Permission

- Install `spatie/laravel-permission`, publish its config, and use its **official migrations**.
- Add the `HasRoles` trait to `User`.
- Register the `role`, `permission` and `role_or_permission` middleware aliases (in `bootstrap/app.php`).
- Use Spatie's standard Roles, Permissions, `HasRoles`, middleware and Gate integration. **Do not build a custom roles/permissions system.**

## 2. Users table additions

Add a migration for:

| Column | Type | Notes |
|--------|------|-------|
| `status` | string (enum `UserStatus`: `active`, `inactive`) | default `active`, indexed |
| `last_login_at` | timestamp, nullable | set on successful login (Task 04) |

Create `App\Enums\UserStatus`. Cast `status` on the model. Add a `HasStatus` trait only if another model (Employee) will share it.

## 3. Roles

Create `App\Enums\RoleName`:

```text
Super Admin
Owner
Administrator
Cashier
Staff
```

These are **system roles**. They cannot be deleted or renamed.

## 4. Permissions

Create `App\Enums\PermissionName` as the single source of truth for permission names (`module.action`):

```text
dashboard.view

users.view
users.create
users.update
users.delete

roles.view
roles.create
roles.update
roles.delete

permissions.view
permissions.create
permissions.update
permissions.delete

employees.view
employees.create
employees.update
employees.delete

audit_trails.view
```

Add helpers such as `module(): string` and `static grouped(): array` for grouping in the UI.

> Do **not** create permissions for sales, inventory, products, suppliers, purchases, expenses, payroll or reports.
>
> Notifications and personal settings (theme) need no permission, because every user manages only their own.

## 5. Default role → permission mapping

| Permission group | Super Admin | Owner | Administrator | Cashier | Staff |
|------------------|:-:|:-:|:-:|:-:|:-:|
| dashboard.view | bypass | ✓ | ✓ | ✓ | ✓ |
| users.* | bypass | ✓ | ✓ | – | – |
| roles.* | bypass | ✓ | view only | – | – |
| permissions.* | bypass | ✓ | view only | – | – |
| employees.* | bypass | ✓ | ✓ | – | – |
| audit_trails.view | bypass | ✓ | ✓ | – | – |

Cashier and Staff start with only `dashboard.view`. They will get module permissions when future modules are added.

## 6. Super Admin

- Grant unrestricted access with Spatie's recommended `Gate::before()` approach, checking `hasRole(RoleName::SuperAdmin->value)`.
- **Never** hardcode authorization by email (`if ($user->email === '...')`).
- Create the first Super Admin through:
  - `AdminUserSeeder`, which reads `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD` from the environment and **fails with a clear message** if they are missing, and
  - an artisan command `php artisan app:create-super-admin` that prompts for name, email and a **hidden** password.
- Never put real credentials in source control.

## 7. Seeders

```text
DatabaseSeeder
 └── RolePermissionSeeder   (idempotent: firstOrCreate, safe to re-run)
 └── AdminUserSeeder
```

Flow:

```text
Permissions → Roles → Role permissions → Initial Super Admin
```

Reset Spatie's permission cache before and after seeding.

## 8. Authorization rules (used by all later tasks)

These rules must be enforced **server-side** (in Services and Policies), not only in the UI:

1. Only a Super Admin can assign or remove the Super Admin role, or edit or delete a Super Admin user.
2. A non-Super-Admin can assign only roles whose permissions are a **subset of their own**. This prevents privilege escalation.
3. Users cannot change their own roles or status, and cannot delete themselves.
4. The **last active Super Admin** cannot be deactivated, deleted or demoted.
5. System roles cannot be deleted or renamed. The Super Admin role's permissions cannot be edited.
6. Client-submitted fields such as `role`, `roles`, `permissions`, `is_admin`, `user_id`, `created_by` and `updated_by` are never trusted. They are set or checked server-side.

Put the reusable checks in one place (for example `RoleService::canAssign(User $actor, Role $role)`) and reuse them.

## 9. Tests

- The seeder creates all roles and permissions and is idempotent (running it twice creates no duplicates).
- Super Admin passes every Gate check.
- Each default role has exactly the mapped permissions.
- `AdminUserSeeder` fails clearly when the env credentials are missing.
- `app:create-super-admin` creates a Super Admin with a hashed password.
- None of the out-of-scope module permissions exist.

## Done when

- [ ] Spatie is installed with its official migrations.
- [ ] `UserStatus`, `RoleName` and `PermissionName` enums exist.
- [ ] Roles, permissions and the mapping are seeded.
- [ ] Super Admin bypasses checks through `Gate::before`.
- [ ] The Super Admin seeder and artisan command work without hardcoded credentials.
- [ ] Escalation-prevention rules are implemented in one reusable place.
- [ ] Tests pass.
