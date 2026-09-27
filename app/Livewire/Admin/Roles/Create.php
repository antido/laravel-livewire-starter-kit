<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Livewire\Concerns\ManagesRolePermissions;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add role')]
class Create extends Component
{
    use InteractsWithActor, ManagesRolePermissions;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    public function mount(): void
    {
        $this->authorize('create', Role::class);
    }

    public function toggleModule(string $module): void
    {
        $this->authorize('create', Role::class);

        $this->toggleModulePermissions($module);
    }

    public function save(RoleService $roles): void
    {
        $this->authorize('create', Role::class);

        /** @var array{name: string, permissions?: list<string>} $validated */
        $validated = $this->validate(StoreRoleRequest::rules(), StoreRoleRequest::fieldMessages());

        $role = $roles->create($this->actor(), $validated);

        session()->flash('success', __('The :name role was created.', ['name' => $role->name]));

        $this->redirectRoute('admin.roles.index');
    }

    public function render(RoleService $roles): View
    {
        return view('livewire.admin.roles.create', [
            'groups' => $this->permissionGroups($roles),
        ]);
    }
}
