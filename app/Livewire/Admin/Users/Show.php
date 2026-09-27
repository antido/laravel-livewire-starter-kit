<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
    }

    public function render(): View
    {
        $this->authorize('view', $this->user);

        $this->user->loadMissing('employee');

        return view('livewire.admin.users.show', [
            'roles' => $this->user->getRoleNames(),
        ])->title($this->user->name);
    }
}
