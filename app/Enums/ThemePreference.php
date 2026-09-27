<?php

declare(strict_types=1);

namespace App\Enums;

enum ThemePreference: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Light',
            self::Dark => 'Dark',
            self::System => 'System',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Light => 'Always use the light theme.',
            self::Dark => 'Always use the dark theme.',
            self::System => 'Follow your device setting.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Light => 'sun',
            self::Dark => 'moon',
            self::System => 'monitor',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
