# Task 13 — Audit Trails

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 13 of 18 · **Depends on:** 01–12 · **Next:** 14 — Employees
>
> **Stack:** Laravel 13 · Livewire · Spatie Laravel Permission · Tailwind · PHPUnit
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Record important actions in an **immutable** audit log, and add a read-only **Audit Trails** viewer under Administration.

## 1. Data model

Migration `audit_logs`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | |
| `user_id` | FK → users, nullable, `nullOnDelete` | actor; null = System |
| `event` | string (enum `AuditEvent`) | indexed |
| `auditable_type` / `auditable_id` | nullable morph | indexed together |
| `description` | string | human-readable summary |
| `old_values` | json, nullable | |
| `new_values` | json, nullable | |
| `ip_address` | string(45), nullable | |
| `user_agent` | string(512), nullable | truncated |
| `created_at` | timestamp | indexed; **no `updated_at`** |

`App\Enums\AuditEvent` (starting set):

```text
created, updated, deleted, restored,
login, logout, login_failed, password_reset,
roles_synced, permissions_synced, status_changed,
employee_account_linked, employee_account_unlinked
```

## 2. Immutability

- The `AuditLog` model throws an exception on `updating` and `deleting`.
- The UI offers no edit or delete actions.
- Optional retention: make the model `Prunable`, controlled by `AUDIT_RETENTION_DAYS` (empty means keep forever). Schedule `model:prune`.

## 3. Writing entries

- **`AuditLogService::record(AuditEvent $event, ?Model $subject, string $description, array $old = [], array $new = [])`** captures the actor, IP and user agent automatically.
- **`Auditable` trait** (for `User`, and later `Employee`): records `created` / `updated` / `deleted` / `restored`. For updates, it stores **only the changed attributes** (dirty old vs new).
- **Redaction:** before storing, remove or mask these keys everywhere: `password`, `password_confirmation`, `current_password`, `remember_token`, `token`, and any `*_secret` or `*_token` keys. Keep this list in config (`config/audit.php`).
- **Explicit service calls:** add these to existing services:
  - `UserService`: role sync (`roles_synced`, with old/new role names), status change, password reset (event only, **never** the password).
  - `RoleService`: permission sync (`permissions_synced`, with old/new permission names), create, update, delete.
  - `PermissionService`: create, rename, delete.
- **Auth events:** add listeners for `Login`, `Logout`, `Failed` (store the attempted email only, **never** the password) and `PasswordReset`.
- Write audit entries inside the same transaction as the change they describe.
- Readable example:
  ```text
  Admin updated user roles
  Actor: admin@example.com   Subject: User #12 (Juan Dela Cruz)
  Old: Staff                 New: Administrator
  ```

## 4. Viewer UI

| Route | Name | Component | Permission |
|-------|------|-----------|------------|
| `/admin/audit-trails` | `admin.audit-trails.index` | `Admin\AuditTrails\Index` | `audit_trails.view` |

- Table columns: **Date/Time** (app timezone), **User** (name or "System"), **Event** (badge), **Subject** (type plus a label or ID), **Description**, **IP Address**.
- Filters: search (description), user, event, subject type, date from/to. Filters are in the query string. Newest first. 25 rows per page.
- Row click opens a **detail drawer or modal** with full metadata and a **before/after table** that highlights changed fields.
- All values are escaped. JSON is shown as formatted text.
- `AuditLogPolicy`: `viewAny` and `view` only.
- The sidebar item **Audit Trails** (from Task 06) now appears for permitted users.

## 5. Tests

- Creating, updating and deleting a user writes entries. Updates store only the changed fields.
- Passwords and tokens never appear in `old_values` or `new_values`.
- Role and permission syncs are logged with old and new values.
- A failed login is logged without a password.
- Audit log entries cannot be updated or deleted through the model.
- A user without `audit_trails.view` gets 403. Filters return the correct rows.

## Done when

- [ ] Immutable audit logging is wired into auth, users, roles and permissions.
- [ ] Sensitive values are redacted.
- [ ] The Audit Trails viewer with filters and a diff view works.
- [ ] Tests pass.
