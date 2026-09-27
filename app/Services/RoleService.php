<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and the single home of the role and privilege-escalation rules (Task 03 §8).
 */
class RoleService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * Whether the actor may give this role to a user (or take it away).
     *
     * Only a Super Admin can assign the Super Admin role. Anyone else can assign a role
     * only when every permission of that role is one they hold themselves.
     */
    public function canAssign(User $actor, Role $role): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($role->isSuperAdmin()) {
            return false;
        }

        $held = $this->permissionNamesOf($actor);

        return $role->permissions->pluck('name')->diff($held)->isEmpty();
    }

    /**
     * Whether the actor may grant this permission to a role (or take it away). A Super Admin may grant any.
     */
    public function canGrant(User $actor, string $permission): bool
    {
        return $actor->isSuperAdmin() || $this->permissionNamesOf($actor)->contains($permission);
    }

    /**
     * The roles the actor may assign, in a stable order.
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('id')
            ->get()
            ->filter(fn (Role $role): bool => $this->canAssign($actor, $role))
            ->values()
            ->toBase();
    }

    public function isSystemRole(Role $role): bool
    {
        return $role->isSystem();
    }

    public function isSuperAdminRole(Role $role): bool
    {
        return $role->isSuperAdmin();
    }

    /**
     * Whether this user is the only active Super Admin left.
     */
    public function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin() || ! $user->isActive()) {
            return false;
        }

        return User::role(RoleName::SuperAdmin->value)
            ->where('status', UserStatus::Active->value)
            ->count() <= 1;
    }

    /**
     * @param  array{name: string, permissions?: list<string>}  $validated
     *
     * @throws ValidationException when the actor may not grant one of the permissions
     */
    public function create(User $actor, array $validated): Role
    {
        $permissions = collect($validated['permissions'] ?? [])->unique()->values();
        $this->ensureCanGrant($actor, $permissions);

        $role = DB::transaction(function () use ($validated, $permissions): Role {
            $role = new Role(['name' => trim($validated['name']), 'guard_name' => 'web']);
            $role->save();
            $role->syncPermissions($permissions->all());

            $this->audit->record(AuditEvent::Created, $role, "Created role {$role->name}", [], [
                'name' => $role->name,
                'permissions' => $permissions->sort()->values()->all(),
            ]);

            return $role;
        });

        $this->forgetCachedPermissions();

        return $role;
    }

    /**
     * @param  array{name: string, permissions?: list<string>}  $validated
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function update(User $actor, Role $role, array $validated): Role
    {
        $name = trim($validated['name']);
        $requested = collect($validated['permissions'] ?? [])->unique()->sort()->values();
        $current = $role->permissions()->pluck('name')->sort()->values();
        $permissionsChanged = $requested->all() !== $current->all();

        if ($role->isSystem() && $name !== $role->name) {
            throw new ActionNotAllowedException(__('System roles cannot be renamed.'));
        }

        if ($role->isSuperAdmin() && $permissionsChanged) {
            throw new ActionNotAllowedException(__('The Super Admin role has every permission; its permissions cannot be edited.'));
        }

        if ($permissionsChanged) {
            // Adding or removing a permission both require holding it.
            $this->ensureCanGrant($actor, $requested->diff($current)->merge($current->diff($requested))->values());
        }

        DB::transaction(function () use ($role, $name, $permissionsChanged, $requested, $current): void {
            $oldName = $role->name;
            $role->name = $name;
            $role->save();

            if ($oldName !== $name) {
                $this->audit->record(AuditEvent::Updated, $role, "Renamed role {$oldName} to {$name}", ['name' => $oldName], ['name' => $name]);
            }

            if ($permissionsChanged) {
                $role->syncPermissions($requested->all());

                $this->audit->record(AuditEvent::PermissionsSynced, $role, "Changed the permissions of role {$name}", [
                    'permissions' => $current->all(),
                ], [
                    'permissions' => $requested->all(),
                ]);
            }
        });

        $this->forgetCachedPermissions();

        return $role;
    }

    /**
     * Delete a custom role. A role that is still assigned to users is not deleted: remove it from
     * those users first. (This starter kit blocks the deletion rather than asking for a confirmation.)
     *
     * @throws ActionNotAllowedException
     */
    public function delete(User $actor, Role $role): void
    {
        if ($role->isSystem()) {
            throw new ActionNotAllowedException(__('System roles cannot be deleted.'));
        }

        $users = $role->users()->count();

        if ($users > 0) {
            throw new ActionNotAllowedException(trans_choice(
                'This role is assigned to :count user. Remove it from them first.|This role is assigned to :count users. Remove it from them first.',
                $users,
                ['count' => $users],
            ));
        }

        DB::transaction(function () use ($role): void {
            $this->audit->record(AuditEvent::Deleted, $role, "Deleted role {$role->name}", [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ]);

            $role->delete();
        });

        $this->forgetCachedPermissions();
    }

    /**
     * Every permission, grouped by module for the role form.
     *
     * @return array<string, list<Permission>>
     */
    public function permissionsByModule(): array
    {
        return Permission::query()->where('guard_name', 'web')->get()->groupedByModule();
    }

    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  Collection<int, string>  $permissions
     *
     * @throws ValidationException
     */
    private function ensureCanGrant(User $actor, Collection $permissions): void
    {
        $denied = $permissions->reject(fn (string $permission): bool => $this->canGrant($actor, $permission))->values();

        if ($denied->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => __('You can only grant permissions you have yourself: :list.', ['list' => $denied->implode(', ')]),
            ]);
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function permissionNamesOf(User $user): Collection
    {
        return $user->getAllPermissions()->pluck('name')->toBase();
    }
}
