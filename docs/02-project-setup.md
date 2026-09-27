# Task 01 — Project Setup & Technology Stack

<!-- standalone:start -->
> **Project:** Laravel Starter Kit · **Task:** 01 of 18 · **Depends on:** — · **Next:** 02 — Architecture & Conventions
>
> Implement only what this task describes. Do not build later tasks early.
<!-- standalone:end -->

## Goal

Create and configure a production-ready Laravel project for the **Laravel Starter Kit**: a clean foundation with authentication, authorization, layout, theming, employees, audit trails and notifications. Business-specific modules come later and are **not** part of this kit.

This task only installs and configures the stack. Features come in later tasks.

## 1. Technology stack

Use:

- Laravel 13
- PHP 8.4+
- MySQL
- Laravel Sanctum
- Laravel Livewire
- Blade
- Tailwind CSS (through Vite)
- Alpine.js (use the copy bundled with Livewire; **do not install a second Alpine instance**)
- Spatie Laravel Permission (installed in Task 03)
- PHPUnit / Laravel testing tools

Follow Laravel conventions. Use the latest stable package versions that are compatible with Laravel 13.

## 2. Installation steps

1. Create the Laravel 13 project.
2. Configure the MySQL connection through `.env`.
3. Install Livewire.
4. Install Sanctum (for example with `php artisan install:api` if that is the Laravel 13 convention). Configure it for session (stateful) authentication.
5. Configure Tailwind CSS with Vite. Confirm that `npm run dev` and `npm run build` both work.
6. Set the application timezone through `APP_TIMEZONE` (for example `Asia/Manila`) and use it consistently.
7. Run the default migrations and confirm the app boots.

## 3. Environment

Create a clean `.env.example` with placeholders only. It must contain no real credentials:

```text
APP_NAME="Laravel Starter Kit"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

REGISTRATION_ENABLED=true
DEFAULT_REGISTRATION_ROLE=Staff

SUPER_ADMIN_NAME=
SUPER_ADMIN_EMAIL=
SUPER_ADMIN_PASSWORD=
```

## 4. Git hygiene

Make sure `.gitignore` covers:

```text
.env
.env.*
!.env.example
/storage/*.key
/vendor
/node_modules
/public/build
/public/hot
```

Keep Laravel's own `storage/` `.gitignore` files. Never commit `APP_KEY`, database passwords, SMTP credentials, API keys or tokens.

## 5. Useful dev commands

Document these for now. The README comes in Task 18.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

## Done when

- [ ] Laravel 13 is installed and boots.
- [ ] The MySQL connection works.
- [ ] Livewire is installed, and Alpine is loaded only once.
- [ ] Sanctum is installed and configured for session authentication.
- [ ] Tailwind builds with Vite.
- [ ] `APP_TIMEZONE` is applied.
- [ ] `.env.example` exists with placeholders only.
- [ ] `.env` is excluded from Git.
