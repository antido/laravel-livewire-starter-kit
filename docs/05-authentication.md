# Task 04 — Authentication

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 04 of 18 · **Depends on:** 01–03 · **Next:** 05 — UI Components
>
> **Stack:** Laravel 13 · PHP 8.4+ · MySQL · Sanctum · Livewire · Tailwind · Spatie Laravel Permission · PHPUnit
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Implement secure session authentication using Laravel Sanctum, Laravel's built-in auth mechanisms, and Livewire pages.

## 1. Functionality

- Login, with remember-me
- Logout
- Registration / signup (can be turned off with `REGISTRATION_ENABLED`)
- Forgot password
- Reset password
- Confirm password (before sensitive actions)
- Email verification (when mail is configured; `MAIL_MAILER=log` in development)
- Session authentication with CSRF protection

**Do not write custom authentication logic** where Laravel's built-in mechanisms (`Auth`, `Password` broker, `MustVerifyEmail`, `password.confirm` middleware) already do the job. Authentication must work with Livewire.

## 2. Registration

- New users receive the role from `DEFAULT_REGISTRATION_ROLE` (default `Staff`). **A role is never taken from the request.**
- When `REGISTRATION_ENABLED=false`, the signup routes return 404 and the Signup buttons are hidden.
- Passwords use Laravel hashing and strong validation (`Password::defaults()`, minimum 8 characters; in production, also require mixed case, numbers and an uncompromised password). Do not add complexity rules that hurt usability without a real security benefit.

## 3. Login

- Use rate limiting: 5 attempts per minute, keyed by email plus IP, with temporary throttling after that.
- Use a **generic** error message for bad credentials, so it never reveals whether an account exists.
- Regenerate the session after login. Update `last_login_at`.
- **Inactive users:** if the credentials are correct but `status = inactive`, log them out immediately and show: *"Your account is inactive. Please contact an administrator."*
- Add an `EnsureUserIsActive` middleware on authenticated routes. It logs out a user who is deactivated while their session is still open.

## 4. Logout

Invalidate the session and regenerate the CSRF token.

## 5. Password reset

Use Laravel's password broker. The reset request must show the **same message** whether or not the email exists.

## 6. Pages (Livewire + Blade)

Create responsive pages that support light and dark mode, using the **guest layout**:

- Login
- Signup
- Forgot Password
- Reset Password
- Verify Email
- Confirm Password

Keep the design clean and minimal: a centred card, the app logo or name, clear labels, and inline validation errors. Use a temporary simple form markup; Task 05 will replace it with shared UI components.

## 7. Routes

```text
routes/
├── web.php     # landing, authenticated app routes
├── auth.php    # guest + auth routes
└── console.php
```

After login, redirect to `/dashboard`. Create a temporary placeholder dashboard view; Task 09 replaces it. Protect it with:

```text
auth → verified (when verification is enabled) → active → permission:dashboard.view
```

## 8. Session security

- Production must support `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax`.
- Never expose session identifiers.

## 9. Tests

- A user can register and receives the default role, never a role from the request.
- Registration is blocked when disabled.
- A user can log in and log out. Invalid credentials are rejected with a generic message.
- Login is throttled after repeated failures.
- An inactive user cannot log in, and an active session is ended after deactivation.
- Guests are redirected from `/dashboard` to login.
- The password reset flow works and does not reveal whether an email exists.
- The session is regenerated on login and invalidated on logout.

## Done when

- [ ] All auth flows work through Livewire pages.
- [ ] Rate limiting and generic errors are in place.
- [ ] Inactive-user handling works.
- [ ] Registration can be turned on or off, and the default role is assigned server-side.
- [ ] Tests pass.
