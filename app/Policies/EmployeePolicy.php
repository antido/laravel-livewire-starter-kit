<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::EmployeesView->value);
    }

    public function view(User $actor, Employee $employee): bool
    {
        return $actor->can(PermissionName::EmployeesView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(PermissionName::EmployeesCreate->value);
    }

    public function update(User $actor, Employee $employee): bool
    {
        return $actor->can(PermissionName::EmployeesUpdate->value) && ! $employee->trashed();
    }

    /**
     * Archive (soft delete).
     */
    public function delete(User $actor, Employee $employee): bool
    {
        return $actor->can(PermissionName::EmployeesDelete->value) && ! $employee->trashed();
    }

    public function restore(User $actor, Employee $employee): bool
    {
        return $actor->can(PermissionName::EmployeesDelete->value) && $employee->trashed();
    }
}
