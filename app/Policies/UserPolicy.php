<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Who may manage which user accounts. The Super Admin passes every check through Gate::before; rules that
 * must hold even for a Super Admin (self-protection, the last Super Admin) live in UserService.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::UsersView->value);
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can(PermissionName::UsersView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(PermissionName::UsersCreate->value);
    }

    /**
     * Only a Super Admin may edit a Super Admin.
     */
    public function update(User $actor, User $user): bool
    {
        return $actor->can(PermissionName::UsersUpdate->value) && ! $user->isSuperAdmin();
    }

    /**
     * Activate or deactivate. Nobody changes their own status.
     */
    public function changeStatus(User $actor, User $user): bool
    {
        return $this->update($actor, $user) && ! $actor->is($user);
    }

    public function resetPassword(User $actor, User $user): bool
    {
        return $this->update($actor, $user);
    }

    /**
     * Nobody deletes themselves, and only a Super Admin may delete a Super Admin.
     */
    public function delete(User $actor, User $user): bool
    {
        return $actor->can(PermissionName::UsersDelete->value) && ! $actor->is($user) && ! $user->isSuperAdmin();
    }
}
