# Task 15 — Notifications (Top Bar)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 15 of 18 · **Depends on:** 01–14 · **Next:** 16 — Security Hardening
>
> **Stack:** Laravel 13 · Livewire · Laravel Notifications (database channel) · Tailwind · Alpine.js · PHPUnit
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Add in-app notifications, with a **bell in the top header** (the Task 06 slot), a dropdown, and a full notifications page.

> Notifications (persistent, stored in the database) are different from toasts (short-lived UI feedback from Task 05). Keep them separate.

## 1. Setup

- Use Laravel's **database** notification channel. Create the `notifications` table (`php artisan make:notifications-table`, or the Laravel 13 equivalent).
- `App\Enums\NotificationType`: `info`, `success`, `warning`, `danger`, which set the icon and colour.
- A base class (for example `App\Notifications\AppNotification`) with a standard payload:

```php
[
    'type'    => NotificationType::Info->value,
    'title'   => 'Your role was updated',
    'message' => 'You are now an Administrator.',
    'url'     => route('dashboard', absolute: false), // internal path only
]
```

- Notifications can implement `ShouldQueue`. They must still work with `QUEUE_CONNECTION=sync`.
- **Never put sensitive data** (passwords, tokens, personal details beyond what's needed) in a payload.

## 2. Initial notifications

Dispatch these from the existing services, **after the transaction commits**:

| Notification | Sent to | Trigger |
|--------------|---------|---------|
| `WelcomeNotification` | the new user | self-registration |
| `NewUserRegisteredNotification` | users with `users.view` | self-registration |
| `RolesChangedNotification` | the affected user | `UserService` role sync |
| `PasswordChangedByAdminNotification` | the affected user | admin password reset |
| `EmployeeAccountLinkedNotification` | the linked user | `EmployeeService` create/link account |

## 3. Header bell

Livewire component `Notifications\Bell`:

- A bell icon with an **unread count badge**. The badge is hidden at 0 and shows `9+` above 9.
- Clicking it opens a dropdown (the shared `dropdown` component) with:
  - the **10 latest** notifications: type icon, title, message (truncated), relative time ("5 min ago"), and an unread dot,
  - **Mark all as read**,
  - **View all** → `/notifications`,
  - an empty state: "You're all caught up."
- Clicking an item marks it read and navigates to its `url`. **Allow only internal URLs.** Reject absolute or external URLs to prevent open redirects.
- Refresh the count with `wire:poll.30s` (poll only the count, and only while the tab is visible). Real-time broadcasting can come later and is out of scope.
- Accessible: `aria-label` "Notifications, N unread", and keyboard navigation in the dropdown.
- Works in light and dark mode and on mobile.

## 4. Notifications page

| Route | Name | Component |
|-------|------|-----------|
| `/notifications` | `notifications.index` | `Notifications\Index` |

- Tabs: **All** and **Unread**. Pagination (20 per page).
- Actions: mark as read or unread, mark all as read, delete a single notification.
- Needs `auth` and `active` only (no permission), because users only ever see **their own** notifications.

## 5. Security

- Always query through `auth()->user()->notifications()`. **Never** look up a notification by ID alone. This prevents IDOR.
- Validate notification IDs as UUIDs, and lock the Livewire properties that hold them.

## 6. Dashboard hook

The Task 09 "You have N unread notifications" line now appears when N > 0.

## 7. Tests

- Each trigger sends the correct notification to the correct user.
- The bell count matches the unread notifications. Mark read and mark all work.
- A user **cannot** read, mark or delete another user's notification (expect 404 or 403).
- External URLs in a payload are not followed.
- No password or token appears in any notification payload.

## Done when

- [ ] The bell with unread count and dropdown is in the header.
- [ ] The `/notifications` page works.
- [ ] The five initial notifications are dispatched from the services.
- [ ] IDOR and open-redirect protections are tested.
- [ ] Tests pass.
