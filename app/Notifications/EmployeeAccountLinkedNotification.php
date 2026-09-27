<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class EmployeeAccountLinkedNotification extends AppNotification
{
    public function __construct(private readonly string $employeeName, private readonly string $employeeNumber)
    {
        parent::__construct();
    }

    protected function type(): NotificationType
    {
        return NotificationType::Success;
    }

    protected function title(): string
    {
        return 'Your account was linked to an employee record';
    }

    protected function message(): string
    {
        return "Your login now belongs to {$this->employeeName} ({$this->employeeNumber}).";
    }

    protected function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
