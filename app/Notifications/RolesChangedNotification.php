<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class RolesChangedNotification extends AppNotification
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(private readonly array $roles)
    {
        parent::__construct();
    }

    protected function type(): NotificationType
    {
        return NotificationType::Info;
    }

    protected function title(): string
    {
        return 'Your roles were updated';
    }

    protected function message(): string
    {
        return $this->roles === []
            ? 'You no longer have any roles.'
            : 'You are now: '.implode(', ', $this->roles).'.';
    }

    protected function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
