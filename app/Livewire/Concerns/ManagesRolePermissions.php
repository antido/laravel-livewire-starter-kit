<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\PermissionName;
use App\Models\Permission;
use App\Services\RoleService;

/**
 * The grouped permission checkboxes shared by the role create and edit pages.
 *
 * @property list<string> $permissions
 */
trait ManagesRolePermissions
{
    /**
     * Select every permission of a module the actor may grant, or clear them all when they are all selected.
     */
    protected function toggleModulePermissions(string $module): void
    {
        $grantable = collect(app(RoleService::class)->permissionsByModule()[$module] ?? [])
            ->map(fn (Permission $permission): string => $permission->name)
            ->filter(fn (string $name): bool => app(RoleService::class)->canGrant($this->actor(), $name))
            ->values();

        if ($grantable->isEmpty()) {
            return;
        }

        $selected = collect($this->permissions);

        $this->permissions = $grantable->diff($selected)->isEmpty()
            ? $selected->diff($grantable)->values()->all()
            : $selected->merge($grantable)->unique()->values()->all();
    }

    /**
     * The groups for the form: each permission with whether the actor may grant it.
     *
     * @return array<string, array{label: string, permissions: list<array{name: string, label: string, grantable: bool}>}>
     */
    protected function permissionGroups(RoleService $roles): array
    {
        $actor = $this->actor();
        $groups = [];

        foreach ($roles->permissionsByModule() as $module => $permissions) {
            $groups[$module] = [
                'label' => PermissionName::moduleLabel($module),
                'permissions' => array_map(fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'label' => PermissionName::actionLabel($permission->action()),
                    'grantable' => $roles->canGrant($actor, $permission->name),
                ], $permissions),
            ];
        }

        return $groups;
    }
}
