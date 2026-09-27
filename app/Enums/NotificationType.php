<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Success => 'check-circle',
            self::Warning => 'alert',
            self::Danger => 'x-circle',
        };
    }

    /**
     * Tailwind classes for the round icon background.
     */
    public function classes(): string
    {
        return match ($this) {
            self::Info => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            self::Success => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            self::Warning => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            self::Danger => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        };
    }
}
