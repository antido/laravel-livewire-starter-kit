<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Http\Requests\User\UpdateUserRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit user')]
class Edit extends Component
{
    use InteractsWithActor;

    #[Locked]
    public User $user;

    public string $name = '';

    public string $email = '';

    /** @var list<string> */
    public array $roles = [];

    public string $status = 'active';

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roles = $user->getRoleNames()->values()->all();
        $this->status = $user->status->value;
    }

    public function save(UserService $users): void
    {
        $this->authorize('update', $this->user);

        /** @var array{name: string, email: string, roles?: list<string>, status: string} $validated */
        $validated = $this->validate(UpdateUserRequest::rulesFor($this->user));

        $saved = $this->attempt(fn () => $users->update($this->actor(), $this->user, $validated));

        if ($saved) {
            session()->flash('success', __('Changes to :name were saved.', ['name' => $this->user->name]));

            $this->redirectRoute('admin.users.show', $this->user);
        }
    }

    public function render(RoleService $roleService): View
    {
        $actor = $this->actor();

        return view('livewire.admin.users.edit', [
            'isSelf' => $actor->is($this->user),
            // Every role is listed; the ones the actor may not assign are shown but cannot be changed.
            'roleOptions' => Role::query()->with('permissions')->orderBy('id')->get()->map(fn (Role $role): array => [
                'name' => $role->name,
                'assignable' => $roleService->canAssign($actor, $role),
            ])->all(),
            'statuses' => UserStatus::options(),
        ]);
    }
}
