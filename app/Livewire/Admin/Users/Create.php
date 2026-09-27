<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Http\Requests\User\StoreUserRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add user')]
class Create extends Component
{
    use InteractsWithActor;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var list<string> */
    public array $roles = [];

    public string $status = 'active';

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(UserService $users): void
    {
        $this->authorize('create', User::class);

        try {
            /** @var array{name: string, email: string, password: string, roles?: list<string>, status: string} $validated */
            $validated = $this->validate(StoreUserRequest::rules());

            $user = $users->create($this->actor(), $validated);
        } finally {
            $this->reset('password', 'password_confirmation');
        }

        session()->flash('success', __(':name was added.', ['name' => $user->name]));

        $this->redirectRoute('admin.users.show', $user);
    }

    public function render(RoleService $roleService): View
    {
        return view('livewire.admin.users.create', [
            'roleOptions' => $roleService->assignableRoles($this->actor())->map(fn ($role): array => [
                'name' => $role->name,
                'assignable' => true,
            ])->all(),
            'statuses' => UserStatus::options(),
        ]);
    }
}
