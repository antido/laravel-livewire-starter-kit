<?php

declare(strict_types=1);

use App\Enums\PermissionName;

/*
|--------------------------------------------------------------------------
| Sidebar Navigation
|--------------------------------------------------------------------------
|
| The single definition of the sidebar (and of the dashboard quick links).
| An item is shown only when its route exists and the user holds its
| permission. A group is shown only when at least one of its items is.
|
| "active" is the route-name pattern that highlights the item, so child
| pages (create, edit, show) keep their parent highlighted.
|
*/

return [
    'sections' => [
        [
            'label' => null,
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'active' => 'dashboard',
                    'icon' => 'dashboard',
                    'permission' => PermissionName::DashboardView->value,
                    'description' => 'Your welcome page.',
                ],
                [
                    'label' => 'Employees',
                    'route' => 'employees.index',
                    'active' => 'employees.*',
                    'icon' => 'employees',
                    'permission' => PermissionName::EmployeesView->value,
                    'description' => 'Staff records and their accounts.',
                    'quick_link' => true,
                ],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.*',
                    'icon' => 'users',
                    'permission' => PermissionName::UsersView->value,
                    'description' => 'Accounts, roles and access.',
                    'quick_link' => true,
                ],
                [
                    'label' => 'Roles',
                    'route' => 'admin.roles.index',
                    'active' => 'admin.roles.*',
                    'icon' => 'roles',
                    'permission' => PermissionName::RolesView->value,
                    'description' => 'What each role is allowed to do.',
                ],
                [
                    'label' => 'Privileges',
                    'route' => 'admin.permissions.index',
                    'active' => 'admin.permissions.*',
                    'icon' => 'key',
                    'permission' => PermissionName::PermissionsView->value,
                    'description' => 'The permissions roles are built from.',
                ],
                [
                    'label' => 'Audit Trails',
                    'route' => 'admin.audit-trails.index',
                    'active' => 'admin.audit-trails.*',
                    'icon' => 'audit',
                    'permission' => PermissionName::AuditTrailsView->value,
                    'description' => 'Who changed what, and when.',
                    'quick_link' => true,
                ],
            ],
        ],
    ],

    /*
    | Account links: not in the sidebar (they live in the user menu), but offered as
    | dashboard quick links. A null permission means every signed-in user sees it.
    */
    'account' => [
        [
            'label' => 'Settings',
            'route' => 'settings.appearance',
            'active' => 'settings.*',
            'icon' => 'settings',
            'permission' => null,
            'description' => 'Theme and personal preferences.',
            'quick_link' => true,
        ],
    ],
];
