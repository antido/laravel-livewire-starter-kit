# Task 08 — Landing Page (Clean & Minimal)

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 08 of 18 · **Depends on:** 01–07 · **Next:** 09 — Dashboard
>
> **Stack:** Laravel 13 · Blade · Tailwind CSS · Alpine.js · shared UI components (Task 05) · theme toggle (Task 07)
>
> Follow `02-architecture-conventions.md`. Implement only what this task describes.
<!-- standalone:end -->

## Goal

Create a public landing page at `/` with a **clean, minimal** design. It should feel calm and modern: lots of whitespace, strong typography and very little decoration.

## 1. Design principles

- Neutral base colours plus the single accent colour from Task 05. No rainbow gradients, stock photos or busy illustrations.
- Large, confident headline and short copy. Every section must earn its place.
- Content width about `max-w-6xl`, with generous vertical spacing between sections.
- Subtle borders or light background tints to separate sections, instead of heavy shadows.
- Fully responsive (mobile-first). Supports light and dark mode.
- Reuse the shared components (`button`, `icon`, `badge`, …). Do not duplicate markup.

## 2. Structure

```text
┌──────────────────────────────────────────────────────────┐
│ Logo  App Name                      ☀/☾   Log in  [Sign up] │  ← sticky, blurred bg
├──────────────────────────────────────────────────────────┤
│                                                          │
│              (small badge: "Starter Kit")                │
│      A clean foundation for your next Laravel app.       │  ← headline
│   Secure sign-in, roles, employees and audit trails —    │  ← one-line subtext
│              ready from day one.                         │
│                                                          │
│            [ Get started ]   [ Log in ]                  │
│                                                          │
├──────────────────────────────────────────────────────────┤
│  Features (3 × 2 grid on desktop, 1 column on mobile)    │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                  │
│  │ icon     │ │ icon     │ │ icon     │                  │
│  │ Title    │ │ Title    │ │ Title    │                  │
│  │ One line │ │ One line │ │ One line │                  │
│  └──────────┘ └──────────┘ └──────────┘                  │
├──────────────────────────────────────────────────────────┤
│  Simple CTA band: "Ready to get started?"  [ Create account ] │
├──────────────────────────────────────────────────────────┤
│  © {year} App Name                         Log in · Sign up │
└──────────────────────────────────────────────────────────┘
```

### Feature cards (exactly these six)

| Title | One-line description |
|-------|----------------------|
| Secure sign-in | Login, password reset and email verification built in. |
| Roles & privileges | Control exactly who can see and do what. |
| Employee records | Manage staff and link them to user accounts. |
| Audit trails | See who changed what, and when. |
| Notifications | Stay updated right from the top bar. |
| Light & dark mode | Comfortable to use, day or night. |

> Do **not** mention sales, inventory, suppliers, purchases, expenses, payroll or reports anywhere on the page.

## 3. Behaviour

- **Guests** see *Log in* and *Sign up* (Sign up is hidden when `REGISTRATION_ENABLED=false`, and *Get started* then points to Log in).
- **Signed-in users** see a single *Go to dashboard* button instead of Log in / Sign up.
- The theme toggle from Task 07 appears in the top bar.
- The app name comes from `config('app.name')`.
- Set the page `<title>` and a meta description. Use semantic HTML (`header`, `main`, `section`, `footer`) and a logical heading order.

## 4. Quality targets

- No layout shift on load. No external image dependencies.
- Lighthouse Accessibility ≥ 95 and Performance ≥ 90 on a production build (`npm run build`).

## 5. Tests

- A guest gets 200 on `/` and sees the Log in and Sign up links.
- A signed-in user sees *Go to dashboard*.
- The Sign up link is hidden when registration is disabled.
- The page does not contain the out-of-scope module names (sales, inventory, suppliers, purchases, expenses, payroll, reports).

## Done when

- [ ] The minimal landing page is live at `/` in light and dark mode.
- [ ] It is responsive from 360 px to desktop.
- [ ] Buttons adapt to guest or signed-in state and to the registration setting.
- [ ] Tests pass.
