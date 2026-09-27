<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class WelcomeNotification extends AppNotification
{
    public function __construct(private readonly string $appName)
    {
        parent::__construct();
    }

    protected function type(): NotificationType
    {
        return NotificationType::Success;
    }

    protected function title(): string
    {
        return "Welcome to {$this->appName}";
    }

    protected function message(): string
    {
        return 'Your account is ready. You can change the theme from Settings.';
    }

    protected function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
