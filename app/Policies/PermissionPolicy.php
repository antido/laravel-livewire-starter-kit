<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Permission;
use App\Models\User;

/**
 * The Super Admin passes every check through Gate::before; PermissionService still refuses to rename
 * or delete a system permission for anyone.
 */
class PermissionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::PermissionsView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(PermissionName::PermissionsCreate->value);
    }

    public function update(User $actor, Permission $permission): bool
    {
        return $actor->can(PermissionName::PermissionsUpdate->value) && ! $permission->isSystem();
    }

    public function delete(User $actor, Permission $permission): bool
    {
        return $actor->can(PermissionName::PermissionsDelete->value) && ! $permission->isSystem();
    }
}
