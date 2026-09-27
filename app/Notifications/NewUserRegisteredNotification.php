<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;

class NewUserRegisteredNotification extends AppNotification
{
    public function __construct(private readonly int $userId, private readonly string $userName)
    {
        parent::__construct();
    }

    public static function for(User $user): self
    {
        return new self($user->id, $user->name);
    }

    protected function type(): NotificationType
    {
        return NotificationType::Info;
    }

    protected function title(): string
    {
        return 'New user signed up';
    }

    protected function message(): string
    {
        return "{$this->userName} created an account.";
    }

    protected function url(): string
    {
        return route('admin.users.show', $this->userId, absolute: false);
    }
}
