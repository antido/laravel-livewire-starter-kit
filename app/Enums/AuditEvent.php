<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';
    case PasswordReset = 'password_reset';
    case RolesSynced = 'roles_synced';
    case PermissionsSynced = 'permissions_synced';
    case StatusChanged = 'status_changed';
    case EmployeeAccountLinked = 'employee_account_linked';
    case EmployeeAccountUnlinked = 'employee_account_unlinked';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Restored => 'Restored',
            self::Login => 'Logged in',
            self::Logout => 'Logged out',
            self::LoginFailed => 'Failed login',
            self::PasswordReset => 'Password reset',
            self::RolesSynced => 'Roles changed',
            self::PermissionsSynced => 'Permissions changed',
            self::StatusChanged => 'Status changed',
            self::EmployeeAccountLinked => 'Account linked',
            self::EmployeeAccountUnlinked => 'Account unlinked',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Created, self::Restored, self::EmployeeAccountLinked => 'success',
            self::Deleted, self::LoginFailed => 'danger',
            self::Login, self::Logout => 'neutral',
            self::PasswordReset, self::StatusChanged, self::EmployeeAccountUnlinked => 'warning',
            self::Updated, self::RolesSynced, self::PermissionsSynced => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $event): array => [$event->value => $event->label()])
            ->all();
    }
}
