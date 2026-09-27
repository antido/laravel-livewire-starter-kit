# Task 10 — User Management (`/admin/users`)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 10 of 18 · **Depends on:** 01–09 · **Next:** 11 — Role Management
>
> **Stack:** Laravel 13 · Livewire · Spatie Laravel Permission · Tailwind · PHPUnit
>
> Follow `02-architecture-conventions.md` and the authorization rules in `03-roles-and-permissions.md` §8. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Build the Livewire User Management pages.

## 1. Routes and components

| Route | Name | Component | Permission |
|-------|------|-----------|------------|
| `/admin/users` | `admin.users.index` | `Admin\Users\Index` | `users.view` |
| `/admin/users/create` | `admin.users.create` | `Admin\Users\Create` | `users.create` |
| `/admin/users/{user}` | `admin.users.show` | `Admin\Users\Show` | `users.view` |
| `/admin/users/{user}/edit` | `admin.users.edit` | `Admin\Users\Edit` | `users.update` |

Protect the routes with middleware **and** `UserPolicy`. Re-authorize inside every Livewire action.

## 2. List page

- Columns: **Name**, **Email**, **Roles** (badges), **Employee** (linked employee name or "—"; filled in after Task 14), **Status** (badge), **Created At**, **Actions**.
- Search (name or email, debounced), **Status** filter, **Role** filter, pagination (15 per page), sortable Name and Created At.
- Filters live in the query string.
- Row actions, each shown only if permitted: View, Edit, Activate/Deactivate (with a confirm dialog), Reset password, Delete (with a confirm dialog).
- Show an empty state when nothing matches.

## 3. Create / Edit form

```text
Name
Email
Password               (create only)
Password Confirmation  (create only)
Roles                  (only roles the actor may assign)
Status
```

- Validation comes from `StoreUserRequest` / `UpdateUserRequest`, reused by the Livewire components.
- Only roles that pass `RoleService::canAssign()` are listed **and** accepted on submit.
- Passwords are never shown or stored in any form other than Laravel's hash.

## 4. Other actions

- **Reset password:** an admin sets a new password (with confirmation), or sends a reset link if mail is configured. This requires `users.update` and a password confirmation from the actor (`password.confirm`).
- **Activate/Deactivate:** follows §8. Users cannot deactivate themselves or the last active Super Admin. A deactivated user's session is ended by `EnsureUserIsActive`.
- **Delete:** Users cannot delete themselves or the last Super Admin. Deleting a user **does not** delete a linked employee; the link is cleared.

## 5. Show page

Show profile details, roles, status, `last_login_at` and created or updated dates. After Task 14, it also shows a link to the employee record when one exists.

## 6. Service

`UserService` handles create, update, sync roles, set status, reset password and delete. It wraps multi-step work in `DB::transaction()`, uses **validated data only**, and never uses `$request->all()`.

## 7. Tests

- An authorized user can create, update and delete a user. An unauthorized user gets 403 on the routes **and** on direct Livewire action calls.
- Validation works (unique email, password rules).
- Roles can be assigned. A non-Super-Admin cannot assign Super Admin or any role above their own permissions, even with a crafted payload.
- A user cannot change their own role or status, or delete themselves.
- The last Super Admin is protected.
- Activate/deactivate works.
- Passwords never appear in responses or rendered HTML.

## Done when

- [ ] The list, create, edit, show, activate/deactivate, reset password and delete features all work.
- [ ] Escalation rules are enforced server-side.
- [ ] Tests pass.
