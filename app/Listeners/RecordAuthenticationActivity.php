<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Audits sign-ins, sign-outs, failed sign-ins and password resets. Registered through Laravel's
 * event discovery (each handle* method listens for the event it type-hints).
 */
class RecordAuthenticationActivity
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->record(
            AuditEvent::Login,
            $event->user,
            $event->remember ? "{$event->user->name} logged in (remembered)" : "{$event->user->name} logged in",
            actor: $event->user,
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->record(AuditEvent::Logout, $event->user, "{$event->user->name} logged out", actor: $event->user);
    }

    /**
     * Only the attempted email is stored, never the password.
     */
    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $email = is_string($email) ? Str::limit(Str::lower(trim($email)), 190, '') : null;
        $user = $event->user instanceof User ? $event->user : null;

        $this->audit->record(
            AuditEvent::LoginFailed,
            $user,
            'Failed login attempt'.($email !== null && $email !== '' ? " for {$email}" : ''),
            new: $email !== null && $email !== '' ? ['email' => $email] : [],
            actor: null,
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->record(AuditEvent::PasswordReset, $event->user, "{$event->user->name} reset their password", actor: $event->user);
    }
}
