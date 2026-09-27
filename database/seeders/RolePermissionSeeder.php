<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the system permissions and roles and gives each role its default permissions.
 * Safe to run again: it only adds what is missing and never removes anything.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value, 'web');

            if ($roleName !== RoleName::SuperAdmin) {
                $role->givePermissionTo(array_map(
                    fn (PermissionName $permission): string => $permission->value,
                    $roleName->defaultPermissions(),
                ));
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
