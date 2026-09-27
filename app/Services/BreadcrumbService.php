<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Routing\Route;

/**
 * The breadcrumb trail for the current page, defined in one place per route name. A crumb links only
 * when the user may open that page; the last crumb is the current page and never links.
 *
 * @phpstan-type Crumb array{label: string, url: string|null}
 */
class BreadcrumbService
{
    /**
     * @return list<Crumb>
     */
    public function for(?Route $route, ?User $user): array
    {
        if ($route === null || $user === null) {
            return [];
        }

        $name = (string) $route->getName();
        $trail = [$this->crumb('Dashboard', 'dashboard', [], $user->can('dashboard.view'))];

        $employee = fn (): ?Employee => $this->model($route, 'employee', Employee::class);
        $account = fn (): ?User => $this->model($route, 'user', User::class);
        $role = fn (): ?Role => $this->model($route, 'role', Role::class);

        $employees = $this->crumb('Employees', 'employees.index', [], $user->can('viewAny', Employee::class));
        $administration = ['label' => 'Administration', 'url' => null];
        $users = $this->crumb('Users', 'admin.users.index', [], $user->can('viewAny', User::class));
        $roles = $this->crumb('Roles', 'admin.roles.index', [], $user->can('viewAny', Role::class));

        $rest = match ($name) {
            'dashboard' => [],
            'employees.index' => [$employees],
            'employees.create' => [$employees, ['label' => 'Add employee', 'url' => null]],
            'employees.show' => [$employees, ['label' => $employee()->full_name ?? 'Employee', 'url' => null]],
            'employees.edit' => [
                $employees,
                ($e = $employee()) !== null ? $this->crumb($e->full_name, 'employees.show', ['employee' => $e], $user->can('view', $e)) : ['label' => 'Employee', 'url' => null],
                ['label' => 'Edit', 'url' => null],
            ],
            'admin.users.index' => [$administration, $users],
            'admin.users.create' => [$administration, $users, ['label' => 'Add user', 'url' => null]],
            'admin.users.show' => [$administration, $users, ['label' => $account()->name ?? 'User', 'url' => null]],
            'admin.users.edit', 'admin.users.reset-password' => [
                $administration,
                $users,
                ($a = $account()) !== null ? $this->crumb($a->name, 'admin.users.show', ['user' => $a], $user->can('view', $a)) : ['label' => 'User', 'url' => null],
                ['label' => $name === 'admin.users.edit' ? 'Edit' : 'Reset password', 'url' => null],
            ],
            'admin.roles.index' => [$administration, $roles],
            'admin.roles.create' => [$administration, $roles, ['label' => 'Add role', 'url' => null]],
            'admin.roles.edit' => [$administration, $roles, ['label' => 'Edit '.($role()->name ?? 'role'), 'url' => null]],
            'admin.permissions.index' => [$administration, ['label' => 'Privileges', 'url' => null]],
            'admin.audit-trails.index' => [$administration, ['label' => 'Audit Trails', 'url' => null]],
            'notifications.index' => [['label' => 'Notifications', 'url' => null]],
            'settings.appearance' => [['label' => 'Settings', 'url' => null], ['label' => 'Appearance', 'url' => null]],
            default => null,
        };

        if ($rest === null) {
            return [];
        }

        $trail = array_merge($trail, $rest);

        // The current page is never a link.
        $trail[count($trail) - 1]['url'] = null;

        return $trail;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return Crumb
     */
    private function crumb(string $label, string $route, array $parameters, bool $linked): array
    {
        return ['label' => $label, 'url' => $linked && \Illuminate\Support\Facades\Route::has($route) ? route($route, $parameters) : null];
    }

    /**
     * The model a route parameter points at, whether or not it has been bound yet.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $class
     * @return TModel|null
     */
    private function model(Route $route, string $parameter, string $class): mixed
    {
        $value = $route->parameter($parameter);

        if ($value instanceof $class) {
            return $value;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $query = $class::query();

        if ($class === Employee::class) {
            $query = Employee::withTrashed();
        }

        return $query->find($value);
    }
}
