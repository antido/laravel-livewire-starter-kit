<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use InteractsWithActor, WithPagination;

    public const int PER_PAGE = 15;

    /**
     * UI sort keys mapped to database columns. Anything else is ignored.
     */
    public const array SORTABLE = ['name' => 'name', 'created_at' => 'created_at'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'role'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, self::SORTABLE)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'role');
        $this->resetPage();
    }

    public function toggleStatus(int $id, UserService $users): void
    {
        $user = User::findOrFail($id);
        $this->authorize('changeStatus', $user);

        $status = $user->isActive() ? UserStatus::Inactive : UserStatus::Active;

        $this->attempt(
            fn () => $users->setStatus($this->actor(), $user, $status),
            $status === UserStatus::Active ? __(':name was activated.', ['name' => $user->name]) : __(':name was deactivated.', ['name' => $user->name]),
        );
    }

    public function delete(int $id, UserService $users): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $this->attempt(fn () => $users->delete($this->actor(), $user), __(':name was deleted.', ['name' => $user->name]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);

        return view('livewire.admin.users.index', [
            'users' => $this->users(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'statuses' => UserStatus::options(),
            'actor' => $this->actor(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    private function users(): LengthAwarePaginator
    {
        $search = mb_substr(trim($this->search), 0, 100);
        $status = UserStatus::tryFrom($this->status);
        $column = self::SORTABLE[$this->sort] ?? 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        return User::query()
            ->with(['roles', 'employee'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($this->role !== '', fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $this->role)))
            ->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->paginate(self::PER_PAGE);
    }
}
