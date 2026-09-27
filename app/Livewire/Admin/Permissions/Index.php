<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Permissions;

use App\Enums\PermissionName;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The Privileges page: permissions grouped by module, with create, rename and delete in a modal.
 */
#[Title('Privileges')]
class Index extends Component
{
    use InteractsWithActor;

    #[Url(except: '')]
    public string $search = '';

    public bool $showForm = false;

    /**
     * The permission being renamed, or null while creating. Set only by the server.
     */
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Permission::class);
    }

    public function create(): void
    {
        $this->authorize('create', Permission::class);

        $this->resetValidation();
        $this->editingId = null;
        $this->name = '';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('update', $permission);

        $this->resetValidation();
        $this->editingId = $permission->id;
        $this->name = $permission->name;
        $this->showForm = true;
    }

    public function save(PermissionService $permissions): void
    {
        $editing = $this->editingId !== null ? Permission::findOrFail($this->editingId) : null;
        $this->authorize($editing ? 'update' : 'create', $editing ?? Permission::class);

        $this->name = mb_strtolower(trim($this->name));

        /** @var array{name: string} $validated */
        $validated = $this->validate(StorePermissionRequest::rulesFor($editing), StorePermissionRequest::fieldMessages());

        $saved = $this->attempt(
            fn () => $editing
                ? $permissions->rename($this->actor(), $editing, $validated)
                : $permissions->create($this->actor(), $validated),
            $editing ? __('The privilege was renamed to :name.', ['name' => $validated['name']]) : __('The :name privilege was created.', ['name' => $validated['name']]),
        );

        if ($saved) {
            $this->showForm = false;
            $this->editingId = null;
            $this->name = '';
        }
    }

    public function delete(int $id, PermissionService $permissions): void
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('delete', $permission);

        $this->attempt(fn () => $permissions->delete($this->actor(), $permission), __('The :name privilege was deleted.', ['name' => $permission->name]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Permission::class);

        $search = mb_strtolower(mb_substr(trim($this->search), 0, 100));

        $groups = Permission::query()
            ->where('guard_name', 'web')
            ->with('roles:id,name')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->get()
            ->groupedByModule();

        return view('livewire.admin.permissions.index', [
            'groups' => collect($groups)->mapWithKeys(fn (array $permissions, string $module): array => [
                $module => ['label' => PermissionName::moduleLabel($module), 'permissions' => $permissions],
            ])->all(),
        ]);
    }
}
