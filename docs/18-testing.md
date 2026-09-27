# Task 17 — Testing & Coverage Review

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 17 of 18 · **Depends on:** 01–16 · **Next:** 18 — README & Final Verification
>
> **Stack:** PHPUnit / Laravel testing tools
>
> Review and complete the test suite. Do not add new features.
<!-- standalone:end -->

## Goal

Make sure the suite gives **meaningful** coverage of critical and security-sensitive code. The aim is real protection, not a high percentage for its own sake.

## 1. Conventions

- Use PHPUnit with Laravel's testing helpers, `RefreshDatabase` and model factories. Tests must never depend on production data.
- Provide factories for `User` (with states `inactive`, `superAdmin` and `withRole($role)`) and `Employee` (with states `withAccount` and `resigned`).
- Seed roles and permissions in a shared test setup, for example a trait that runs `RolePermissionSeeder`.
- Test Livewire components with `Livewire::test()` and `Livewire::actingAs()`, including **direct action calls** that bypass the UI.

## 2. Required coverage matrix

| Area | Must cover |
|------|-----------|
| Authentication | register, login, invalid credentials, logout, throttling, password reset, email verification, inactive users, CSRF/session protections |
| Authorization | route middleware, policies, Livewire actions, Super Admin bypass, privilege escalation rules |
| Users | CRUD, validation, role assignment, activate/deactivate, self-protection, last Super Admin |
| Roles | CRUD, permission assignment, system-role protections, cannot grant unheld permissions |
| Privileges | create with format validation, system permission protection, enforcement |
| Employees | CRUD, archive/restore, create/link/unlink account, status sync, number generation |
| Audit Trails | entries written, only dirty fields, redaction, immutability, viewer permissions and filters |
| Notifications | dispatch triggers, bell count, mark read/all, IDOR protection, internal URLs only |
| Theme | save valid values, reject invalid, own-preference only |
| Layout & Dashboard | menu visibility by permission, no out-of-scope modules, welcome page, quick links |
| Landing | guest/auth CTA states, registration toggle, no out-of-scope module text |
| Security | crafted payloads, direct route/action bypass, security headers, debug off, no password exposure |

## 3. Gaps to look for

- Any Livewire action without a matching "unauthorized user gets 403" test.
- Any Form Request without a validation failure test.
- Any service method that writes data without a test for its audit entry.

## 4. Coverage report

If Xdebug or PCOV is available, generate a coverage report (`php artisan test --coverage`). Aim for strong coverage of `app/Services`, `app/Policies`, `app/Http/Middleware` and `app/Livewire`. Record the results in the README.

## Done when

- [ ] Every row in the matrix has passing tests.
- [ ] Every Livewire action has an authorization test.
- [ ] `php artisan test` passes.
