<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\PermissionName;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, Permission>
 */
class PermissionCollection extends Collection
{
    /**
     * Permissions grouped by module: the system modules first, in their declared order, then custom modules
     * alphabetically. Within a module, system permissions keep their declared order and custom ones follow.
     *
     * @return array<string, list<Permission>>
     */
    public function groupedByModule(): array
    {
        $moduleOrder = array_flip(array_keys(PermissionName::grouped()));
        $permissionOrder = array_flip(PermissionName::values());

        $sorted = $this->sort(function (Permission $a, Permission $b) use ($moduleOrder, $permissionOrder): int {
            return [$moduleOrder[$a->module()] ?? PHP_INT_MAX, $a->module(), $permissionOrder[$a->name] ?? PHP_INT_MAX, $a->name]
                <=> [$moduleOrder[$b->module()] ?? PHP_INT_MAX, $b->module(), $permissionOrder[$b->name] ?? PHP_INT_MAX, $b->name];
        });

        $groups = [];

        foreach ($sorted as $permission) {
            $groups[$permission->module()][] = $permission;
        }

        return $groups;
    }
}
