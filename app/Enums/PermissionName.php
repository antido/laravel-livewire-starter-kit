<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The single source of truth for the system permission names (`module.action`).
 */
enum PermissionName: string
{
    case DashboardView = 'dashboard.view';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    case PermissionsView = 'permissions.view';
    case PermissionsCreate = 'permissions.create';
    case PermissionsUpdate = 'permissions.update';
    case PermissionsDelete = 'permissions.delete';

    case EmployeesView = 'employees.view';
    case EmployeesCreate = 'employees.create';
    case EmployeesUpdate = 'employees.update';
    case EmployeesDelete = 'employees.delete';

    case AuditTrailsView = 'audit_trails.view';

    public function module(): string
    {
        return self::moduleOf($this->value);
    }

    public function action(): string
    {
        return self::actionOf($this->value);
    }

    /**
     * The module part of any permission name, e.g. "users" for "users.view".
     */
    public static function moduleOf(string $permission): string
    {
        return str_contains($permission, '.') ? strstr($permission, '.', true) : $permission;
    }

    /**
     * The action part of any permission name, e.g. "view" for "users.view".
     */
    public static function actionOf(string $permission): string
    {
        return str_contains($permission, '.') ? substr((string) strstr($permission, '.'), 1) : $permission;
    }

    /**
     * A readable module name, e.g. "Audit Trails" for "audit_trails".
     */
    public static function moduleLabel(string $module): string
    {
        return match ($module) {
            'permissions' => 'Privileges',
            default => ucwords(str_replace('_', ' ', $module)),
        };
    }

    /**
     * A readable action name, e.g. "View" for "view".
     */
    public static function actionLabel(string $action): string
    {
        return ucwords(str_replace('_', ' ', $action));
    }

    public static function isSystem(string $permission): bool
    {
        return self::tryFrom($permission) !== null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The system permissions grouped by module, in declaration order.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->module()][] = $permission;
        }

        return $groups;
    }
}
