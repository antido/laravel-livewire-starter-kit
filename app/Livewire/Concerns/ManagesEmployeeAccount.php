<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Employee;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Support\Collection;

/**
 * The "System access" section shared by the employee create and edit pages.
 */
trait ManagesEmployeeAccount
{
    /**
     * Search text for the "link an existing user" picker.
     */
    public string $linkSearch = '';

    /**
     * Whether the actor sees the System access section at all.
     */
    protected function canManageAccounts(): bool
    {
        $actor = $this->actor();

        return $actor->can('create', User::class) || $actor->can('users.update');
    }

    /**
     * Users without an employee record that match the search (at most 20).
     *
     * @return Collection<int, User>
     */
    protected function linkableUsers(): Collection
    {
        if (! $this->actor()->can('users.update')) {
            return collect();
        }

        $search = mb_substr(trim($this->linkSearch), 0, 100);

        return User::query()
            ->whereNotIn('id', Employee::withTrashed()->whereNotNull('user_id')->select('user_id'))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'status'])
            ->filter(fn (User $user): bool => $this->actor()->can('update', $user))
            ->values();
    }

    /**
     * @return list<string>
     */
    protected function assignableRoleNames(): array
    {
        return app(RoleService::class)->assignableRoles($this->actor())->pluck('name')->all();
    }
}
