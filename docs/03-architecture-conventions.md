# Task 02 — Architecture & Coding Conventions

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 02 of 18 · **Depends on:** 01 · **Next:** 03 — Roles & Permissions
>
> This file is also the **reference for every later task**. Keep it in context when you work on Tasks 03–18.
<!-- standalone:end -->

## Goal

Set up the folder structure and quality tooling, and define the conventions every later task must follow.

## 1. Folder structure

Use a clean, service-oriented structure:

```text
app/
├── Collections/
├── Console/Commands/
├── Enums/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── User/
│   │   ├── Role/
│   │   ├── Permission/
│   │   ├── Employee/
│   │   └── Settings/
│   └── Resources/
├── Livewire/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Employees/
│   ├── Notifications/
│   ├── Settings/
│   ├── Admin/
│   │   ├── Users/
│   │   ├── Roles/
│   │   ├── Permissions/
│   │   └── AuditTrails/
│   └── Components/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Services/
├── Traits/
└── Providers/
```

Create folders only when the first file needs them. Do not add empty placeholder classes.

> A `Repositories/` layer is **optional**. Use one only if it adds real value over Eloquent. Do not wrap Eloquent just to add a layer.

## 2. Services

Business logic lives in Services, not in controllers, Livewire components or Blade.

```text
app/Services/
├── UserService.php
├── RoleService.php
├── PermissionService.php
├── EmployeeService.php
├── AuditLogService.php
└── UserPreferenceService.php
```

Livewire components coordinate the UI: they validate, authorize and call Services.

## 3. Form Requests and validation

- Use Form Requests for controller endpoints (`StoreUserRequest`, `UpdateUserRequest`, `StoreRoleRequest`, `UpdateRoleRequest`, `StorePermissionRequest`, `StoreEmployeeRequest`, `UpdateEmployeeRequest`, `UpdateAppearanceRequest`).
- In Livewire components, reuse those rules, for example by exposing them through a static `rules()` method on the request class or a dedicated rules class. **Never duplicate** validation rules.
- All validation happens server-side.

## 4. API Resources

Use Resources for any JSON output: `UserResource`, `RoleResource`, `PermissionResource`, `EmployeeResource`, `AuditLogResource`, `NotificationResource`.

Never expose `password`, password hashes, `remember_token` or sensitive internal fields.

## 5. Collections

Create custom Collection classes only when they add domain behaviour, for example `PermissionCollection::groupedByModule()`. Otherwise use Laravel's standard Collection.

## 6. Traits

Keep traits small, and create one only when behaviour is genuinely shared:

```text
app/Traits/
├── HasStatus.php
├── HasAuditFields.php   // created_by / updated_by, set server-side
└── Auditable.php        // writes audit log entries (Task 13)
```

## 7. Enums

Use backed PHP Enums for fixed values. No magic strings.

```text
app/Enums/
├── UserStatus.php
├── EmployeeStatus.php
├── RoleName.php
├── PermissionName.php
├── ThemePreference.php
├── AuditEvent.php
└── NotificationType.php
```

Each enum is created in the task that first needs it.

## 8. Policies

Create Policies for sensitive models: `UserPolicy`, `RolePolicy`, `PermissionPolicy`, `EmployeePolicy`, `AuditLogPolicy`. Cover view, create, update, delete, restore and any sensitive actions.

## 9. Transactions

Wrap multi-step operations in `DB::transaction()`. For example, *create user → assign role → write audit log → send notification* must roll back as a whole if a critical step fails. Dispatch notifications **after commit**.

## 10. Livewire rules

- Keep components small and focused, with one per page or action (`Index`, `Create`, `Edit`, `Show`).
- **Authorize inside every action method**, not only in `mount()`.
- Lock or guard any public property that must not be changed by the client (IDs, roles, ownership), for example with `#[Locked]`.
- Components call Services for anything non-trivial.

## 11. Code quality

Follow PSR-12, Laravel conventions and SOLID where practical. Use strict types and full type declarations, small methods and meaningful names.

Avoid massive controllers or components, duplicated validation or logic, magic strings, hardcoded credentials and authorization checks based on email addresses.

## 12. Tooling (implement now)

1. Install and configure **Laravel Pint** (add a `pint.json` with the Laravel preset).
2. Install and configure **Larastan** (`phpstan.neon`, starting at level 6 or higher and raising it where practical).
3. Add composer scripts:
   ```json
   "lint": "pint --test",
   "format": "pint",
   "analyse": "phpstan analyse",
   "test": "php artisan test"
   ```
4. Confirm all three pass on the fresh project.

Fix issues rather than suppressing them. Justify any baseline or ignore entry in a comment.

## Done when

- [ ] Base folders exist where needed.
- [ ] Pint is configured and passing.
- [ ] Larastan is configured and passing.
- [ ] Composer scripts for lint, format, analyse and test exist.
