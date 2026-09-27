<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Seeds the system roles and permissions before each test (Laravel calls setUp{TraitName} automatically).
 */
trait SeedsRolesAndPermissions
{
    protected function setUpSeedsRolesAndPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function userWithRole(RoleName|string $role): User
    {
        return User::factory()->withRole($role)->create();
    }

    protected function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }
}
