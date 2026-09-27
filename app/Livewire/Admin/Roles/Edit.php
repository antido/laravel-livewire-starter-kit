<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Livewire\Concerns\ManagesRolePermissions;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit role')]
class Edit extends Component
{
    use InteractsWithActor, ManagesRolePermissions;

    #[Locked]
    public Role $role;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    public function mount(Role $role): void
    {
        $this->authorize('update', $role);

        $this->role = $role;
        $this->name = $role->name;
        $this->permissions = $role->permissions()->pluck('name')->values()->all();
    }

    public function toggleModule(string $module): void
    {
        $this->authorize('update', $this->role);

        if ($this->role->isSuperAdmin()) {
            return;
        }

        $this->toggleModulePermissions($module);
    }

    public function save(RoleService $roles): void
    {
        $this->authorize('update', $this->role);

        /** @var array{name: string, permissions?: list<string>} $validated */
        $validated = $this->validate(UpdateRoleRequest::rulesFor($this->role), StoreRoleRequest::fieldMessages());

        $saved = $this->attempt(fn () => $roles->update($this->actor(), $this->role, $validated));

        if ($saved) {
            session()->flash('success', __('The :name role was saved.', ['name' => $this->role->name]));

            $this->redirectRoute('admin.roles.index');
        }
    }

    public function render(RoleService $roles): View
    {
        return view('livewire.admin.roles.edit', [
            'groups' => $this->permissionGroups($roles),
        ]);
    }
}
