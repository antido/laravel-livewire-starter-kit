# Task 12 — Privileges / Permission Management (`/admin/permissions`)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 12 of 18 · **Depends on:** 01–11 · **Next:** 13 — Audit Trails
>
> **Stack:** Laravel 13 · Livewire · Spatie Laravel Permission · Tailwind · PHPUnit
>
> Follow `02-architecture-conventions.md` and `03-roles-and-permissions.md` §8. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Build the permission management interface. The sidebar labels it **Privileges**.

## 1. Routes

| Route | Name | Permission |
|-------|------|------------|
| `/admin/permissions` | `admin.permissions.index` | `permissions.view` |

Create, edit and delete open in modals on the index page and require `permissions.create`, `permissions.update` and `permissions.delete`. Use `PermissionPolicy` and re-authorize in every action.

## 2. Features

- List permissions **grouped by module**, with a search box:

```text
Users
    users.view
    users.create
    users.update
    users.delete

Employees
    employees.view
    employees.create
    employees.update
    employees.delete

Audit Trails
    audit_trails.view
```

- Show the roles using each permission, as badges.
- **Create** a permission. The name must match `^[a-z_]+\.[a-z_]+$` (`module.action`) and be unique.
- **Edit** a permission name (custom permissions only).
- **Delete** a permission where safe.

## 3. Safeguards

- **System permissions** (every value in `PermissionName`) cannot be renamed or deleted.
- A custom permission that is assigned to any role cannot be deleted until it is removed from those roles, or only after a confirmation that lists the affected roles. Pick one approach and document it.
- Clear Spatie's permission cache after changes.

## 4. Service

`PermissionService` handles create, rename and delete, including the safeguards above.

## 5. Tests

- An authorized user can create a custom permission, and the format is validated.
- System permissions cannot be renamed or deleted.
- Permissions are enforced: a user without `permissions.view` gets 403.
- Unauthorized actions fail on routes and on direct Livewire calls.

## Done when

- [ ] Grouped list, search, create, edit and delete work, with safeguards.
- [ ] Tests pass.
