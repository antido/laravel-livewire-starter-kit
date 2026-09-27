<?php

declare(strict_types=1);

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On leave',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::OnLeave => 'warning',
            self::Resigned => 'neutral',
            self::Terminated => 'danger',
        };
    }

    /**
     * Whether the employee has left, so a linked account is usually deactivated too.
     */
    public function hasLeft(): bool
    {
        return $this === self::Resigned || $this === self::Terminated;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
