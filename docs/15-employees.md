# Task 14 — Employees (Linked to the User Module)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 14 of 18 · **Depends on:** 01–13 · **Next:** 15 — Notifications
>
> **Stack:** Laravel 13 · Livewire · Spatie Laravel Permission · Tailwind · PHPUnit
>
> Follow `02-architecture-conventions.md` and `03-roles-and-permissions.md` §8. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Add an **Employees** module, shown in the sidebar under Dashboard. Each employee **can optionally be linked to one user account**, so an employee can log in with the roles given to that account.

> This covers employee **records** only. Payroll, schedules and attendance are out of scope.

## 1. Relationship with Users

```text
User 1 ─── 0..1 Employee
```

- `employees.user_id` is nullable, **unique** and `nullOnDelete`.
- `User::employee()` is `hasOne`; `Employee::user()` is `belongsTo`.
- An employee may exist **without** a login account (for example a helper who never uses the system).
- A user may exist **without** an employee record (for example the owner).
- Deleting an employee **does not** delete the user account. Deleting a user clears the link.

## 2. Data model

Migration `employees`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | |
| `user_id` | FK users, nullable, unique | login account |
| `employee_number` | string, unique | generated server-side, for example `EMP-00001` |
| `first_name`, `middle_name` (nullable), `last_name`, `suffix` (nullable) | string | |
| `email` | string, nullable | contact email (can differ from the login email) |
| `phone` | string, nullable | |
| `address` | text, nullable | |
| `position` | string | |
| `hire_date` | date, nullable | |
| `status` | string (enum `EmployeeStatus`: `active`, `on_leave`, `resigned`, `terminated`) | indexed |
| `notes` | text, nullable | |
| `created_by`, `updated_by` | FK users, nullable | set server-side (`HasAuditFields`) |
| timestamps, `deleted_at` | | soft deletes |

Add the `Auditable` trait (Task 13) to `Employee`. Add a `full_name` accessor.

## 3. Routes

| Route | Name | Component | Permission |
|-------|------|-----------|------------|
| `/employees` | `employees.index` | `Employees\Index` | `employees.view` |
| `/employees/create` | `employees.create` | `Employees\Create` | `employees.create` |
| `/employees/{employee}` | `employees.show` | `Employees\Show` | `employees.view` |
| `/employees/{employee}/edit` | `employees.edit` | `Employees\Edit` | `employees.update` |

Delete and restore are actions that require `employees.delete`. Use `EmployeePolicy` and re-authorize in every action.

## 4. List page

- Columns: **Employee No.**, **Name**, **Position**, **Status** (badge), **Account** (linked user email with a status badge, or "No account"), **Hire Date**, **Actions**.
- Search (name, employee number, position), **Status** filter, **Has account** filter (yes/no), a **Show archived** toggle, and pagination.

## 5. Create / Edit form

**Section 1: Employee details**, with the fields from the table above.

**Section 2: System access** (optional)

```text
( ) No login account
( ) Create a new user account   → Login email, Password, Confirm, Roles
( ) Link an existing user        → searchable select of users that have no employee
```

- Creating a new account also requires **`users.create`**. Linking or unlinking an existing user requires **`users.update`**. Users who lack these permissions do not see the section, and the server rejects the request anyway.
- Roles offered follow `RoleService::canAssign()`.
- A new user's name is set from the employee's full name.
- **Unlink account** is available on Edit (with a confirm dialog). It never deletes the user.

## 6. Status sync

When an employee's status changes to `resigned` or `terminated` and they have a linked account, show a checkbox **"Also deactivate the linked user account"** (checked by default). This is applied through `UserService` inside the same transaction, and the Super Admin rules from §8 still apply.

## 7. Show page

Show the employee details and account panel (email, roles, status, last login, link to `/admin/users/{user}` when the viewer has `users.view`), plus a **Recent activity** list of the 10 latest audit entries for this employee, visible only with `audit_trails.view`.

## 8. Update the User module (Task 10)

- Fill in the **Employee** column on `/admin/users`.
- The user Show page links to the employee record when one exists.
- Deleting a user shows a warning if an employee is linked. The employee stays, and the link is cleared.

## 9. Service

`EmployeeService` handles create, update, archive, restore, `createAccount`, `linkAccount`, `unlinkAccount` and `generateEmployeeNumber()` (safe under concurrency). It reuses `UserService` for anything user-related. Everything runs in transactions and writes audit entries (`employee_account_linked` / `employee_account_unlinked`).

## 10. Tests

- Employee create, update, archive and restore work with permissions. Unauthorized requests get 403 on routes and Livewire actions.
- Creating an employee **with a new account** creates both records and links them. Without `users.create`, this is rejected even with a crafted payload.
- Linking fails for a user who already has an employee (unique constraint and validation).
- Unlinking keeps the user. Deleting the user keeps the employee and clears `user_id`.
- The deactivate-on-resign option deactivates the linked user.
- Employee numbers are unique and generated server-side (client input is ignored).
- Audit entries are written for employee changes and link or unlink events.

## Done when

- [ ] Employees CRUD with archive and restore works.
- [ ] Employees can be linked to users: create a new account, link an existing one, or unlink.
- [ ] The Users list and Show page show the linked employee.
- [ ] Employees appears in the sidebar for permitted users.
- [ ] Tests pass.
