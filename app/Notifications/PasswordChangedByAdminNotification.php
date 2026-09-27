<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class PasswordChangedByAdminNotification extends AppNotification
{
    protected function type(): NotificationType
    {
        return NotificationType::Warning;
    }

    protected function title(): string
    {
        return 'Your password was changed';
    }

    protected function message(): string
    {
        return 'An administrator set a new password for your account. Contact them if you did not expect this.';
    }

    protected function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
