<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Livewire\Concerns\InteractsWithActor;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Roles')]
class Index extends Component
{
    use InteractsWithActor, WithPagination;

    public const int PER_PAGE = 15;

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id, RoleService $roles): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);

        $this->attempt(fn () => $roles->delete($this->actor(), $role), __('The :name role was deleted.', ['name' => $role->name]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('livewire.admin.roles.index', [
            'roles' => $this->roles(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    private function roles(): LengthAwarePaginator
    {
        $search = mb_substr(trim($this->search), 0, 100);

        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }
}
