<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The system roles. They cannot be deleted or renamed.
 */
enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Owner = 'Owner';
    case Administrator = 'Administrator';
    case User = 'User';

    public static function isSystem(string $role): bool
    {
        return self::tryFrom($role) !== null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The permissions this role is seeded with. The Super Admin gets none: it passes every check through the Gate.
     *
     * @return list<PermissionName>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Owner => PermissionName::cases(),
            self::Administrator => [
                PermissionName::DashboardView,
                PermissionName::UsersView,
                PermissionName::UsersCreate,
                PermissionName::UsersUpdate,
                PermissionName::UsersDelete,
                PermissionName::RolesView,
                PermissionName::PermissionsView,
                PermissionName::EmployeesView,
                PermissionName::EmployeesCreate,
                PermissionName::EmployeesUpdate,
                PermissionName::EmployeesDelete,
                PermissionName::AuditTrailsView,
            ],
            self::User => [PermissionName::DashboardView],
        };
    }
}
