<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;

/**
 * The Super Admin passes every check through Gate::before; the rules that bind even a Super Admin
 * (system roles, the Super Admin role's permissions) are enforced in RoleService.
 */
class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::RolesView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(PermissionName::RolesCreate->value);
    }

    /**
     * The Super Admin role is edited by nobody but a Super Admin (and even then only its bypass applies).
     */
    public function update(User $actor, Role $role): bool
    {
        return $actor->can(PermissionName::RolesUpdate->value) && ! $role->isSuperAdmin();
    }

    /**
     * System roles are never deleted.
     */
    public function delete(User $actor, Role $role): bool
    {
        return $actor->can(PermissionName::RolesDelete->value) && ! $role->isSystem();
    }
}
