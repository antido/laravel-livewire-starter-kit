# Task 11 — Role Management (`/admin/roles`)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 11 of 18 · **Depends on:** 01–10 · **Next:** 12 — Permission Management
>
> **Stack:** Laravel 13 · Livewire · Spatie Laravel Permission · Tailwind · PHPUnit
>
> Follow `02-architecture-conventions.md` and `03-roles-and-permissions.md` §8. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Build the Livewire pages to list, create, edit and delete roles and assign their permissions.

## 1. Routes

| Route | Name | Permission |
|-------|------|------------|
| `/admin/roles` | `admin.roles.index` | `roles.view` |
| `/admin/roles/create` | `admin.roles.create` | `roles.create` |
| `/admin/roles/{role}/edit` | `admin.roles.edit` | `roles.update` |

Delete is an action on the index page and requires `roles.delete`. Use `RolePolicy` and re-authorize in every action.

## 2. List

Columns: **Role Name**, **Permissions** (count), **Users** (count), **System** (badge for system roles), **Actions**. Include search and pagination.

## 3. Form

```text
Role Name
Permissions (grouped by module)
```

Group permissions by module, using `PermissionName::grouped()` or `PermissionCollection::groupedByModule()`:

```text
Dashboard
[x] View

Users
[x] View  [x] Create  [x] Update  [ ] Delete

Roles
[x] View  [ ] Create  [ ] Update  [ ] Delete

Privileges
[x] View  [ ] Create  [ ] Update  [ ] Delete

Employees
[x] View  [x] Create  [x] Update  [ ] Delete

Audit Trails
[x] View
```

- Each module has a **Select all** toggle.
- An actor can grant only permissions they hold themselves. The Super Admin can grant any.

## 4. Rules

- System roles (Super Admin, Owner, Administrator, Cashier, Staff) cannot be deleted or renamed.
- The Super Admin role's permissions cannot be edited. It uses the Gate bypass.
- A role that is assigned to users cannot be deleted without an explicit confirmation that states how many users it affects. Alternatively, block the deletion. Pick one approach and document it.
- Clear Spatie's permission cache after any change.

## 5. Service

`RoleService` handles create, update, sync permissions and delete, all in transactions.

## 6. Tests

- An authorized admin can create a role and assign permissions. An unauthorized user cannot, on routes or Livewire actions.
- System roles cannot be deleted or renamed.
- The Super Admin role's permissions are not editable.
- An actor cannot grant permissions they do not have.
- Permission changes take effect immediately (the cache is cleared).

## Done when

- [ ] Role CRUD and grouped permission assignment with Select all work.
- [ ] System-role protections are in place.
- [ ] Tests pass.
