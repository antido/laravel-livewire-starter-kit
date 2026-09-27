# Task 16 — Security Hardening Pass

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 16 of 18 · **Depends on:** 01–15 · **Next:** 17 — Testing
>
> **Stack:** Laravel 13 · PHP 8.4+ · MySQL · Sanctum · Livewire · Spatie Laravel Permission
>
> Review **all existing code** against this list and fix any gaps. Do not add new features.
<!-- standalone:end -->

## Goal

Treat security as a first-class requirement. Audit the whole codebase for common web vulnerabilities and harden it.

## 1. Threats to cover

SQL injection · XSS · CSRF · broken access control · mass assignment · session fixation · authentication bypass · privilege escalation · IDOR · sensitive data exposure · file upload abuse · brute-force login · excessive request abuse · open redirects.

## 2. Checklist

### Access control

- [ ] Every route has `auth` plus `active` plus a permission middleware or policy check.
- [ ] Every Livewire **action** re-authorizes. Checking in `mount()` alone is not enough.
- [ ] Livewire properties that hold IDs, roles or ownership are `#[Locked]` or re-validated.
- [ ] Route model binding is scoped or authorized, so one user cannot reach another's records.
- [ ] Privilege escalation rules (Task 03 §8) are enforced in services.

### Mass assignment

- [ ] No `Model::create($request->all())` or `->update($request->all())` anywhere.
- [ ] `$fillable` is explicit. `is_admin`, `role`, `permissions`, `user_id`, `created_by`, `updated_by` and `employee_number` are set server-side only.

### Input validation

- [ ] Validate every user-controlled input (query, form, route params, headers, hidden fields, Livewire properties) for type, length, format, range, existence and uniqueness.

### SQL

- [ ] Use Eloquent, the Query Builder or parameterized queries only. There is no string-concatenated SQL.
- [ ] Sort and filter columns are whitelisted. User input never becomes an `orderBy` column directly.

### XSS

- [ ] There is no `{!! !!}` with untrusted data. Audit log JSON and notification text are escaped.

### Passwords and secrets

- [ ] Passwords are never stored in plaintext, logged, returned, included in exceptions or written to audit logs.
- [ ] `.env` is not committed. `.env.example` has placeholders only. No secrets are in the repo history.

### Sessions and login

- [ ] Session is regenerated on login and invalidated on logout.
- [ ] Production supports `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax`.
- [ ] Login is rate limited, with generic error messages.
- [ ] Other sensitive endpoints are rate limited too (password reset, registration, notification polling).

### File uploads (for any future upload)

- [ ] Validate MIME type, extension and size server-side. Generate safe filenames. Store outside public or executable paths. Block executable types.

### Error handling

- [ ] With `APP_DEBUG=false`, there are no stack traces, SQL, file paths or environment values in responses.
- [ ] Custom error pages (403, 404, 419, 429, 500) match the design and support dark mode.

### HTTP security headers

Add a middleware for production that sets, **after testing that Livewire, Alpine and the theme script still work**:

- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (disable unused features)
- `X-Frame-Options: SAMEORIGIN` (or `frame-ancestors` in CSP)
- `Strict-Transport-Security` (only when HTTPS is fully enabled)
- **Content-Security-Policy** with a nonce (`Vite::useCspNonce()`), applied to the Task 07 inline theme script and to Livewire or Alpine as needed. Document any `unsafe-*` exception and why it is needed.

### Logging

- [ ] Logs never contain passwords, tokens, session secrets or API keys.
- [ ] Administrative actions go to the audit trail (Task 13).

### Open redirects

- [ ] Post-login `intended` redirects and notification URLs allow only internal paths.

## 3. Dependency audit

```bash
composer audit
npm audit
```

Fix vulnerabilities, or document each accepted exception with its reason and review date. Do not blindly ignore advisories.

## 4. Tests to add

- Direct HTTP and Livewire calls without permission fail for **every** admin and employee action.
- Crafted payloads (extra `roles`, `user_id` or `created_by` fields) are ignored or rejected.
- Security headers are present in production mode.
- `APP_DEBUG=false` hides error details.

## Done when

- [ ] Every checklist item is verified or fixed.
- [ ] Security headers and CSP are tested alongside Livewire and theme functionality.
- [ ] `composer audit` and `npm audit` are clean or have documented exceptions.
- [ ] Tests pass.
