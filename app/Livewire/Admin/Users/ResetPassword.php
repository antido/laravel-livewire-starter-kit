<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * An administrator sets a new password for a user, or emails them a reset link. The route sits behind
 * Laravel's password.confirm middleware; the actions check the confirmation again because Livewire
 * requests do not pass through route middleware.
 */
#[Title('Reset password')]
class ResetPassword extends Component
{
    use InteractsWithActor;

    #[Locked]
    public User $user;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->authorize('resetPassword', $user);

        $this->user = $user;
    }

    public function save(UserService $users): void
    {
        $this->authorize('resetPassword', $this->user);

        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        try {
            /** @var array{password: string} $validated */
            $validated = $this->validate(ResetUserPasswordRequest::rules());

            $saved = $this->attempt(fn () => $users->resetPassword($this->actor(), $this->user, $validated['password']));
        } finally {
            $this->reset('password', 'password_confirmation');
        }

        if ($saved) {
            session()->flash('success', __('The password for :name was changed.', ['name' => $this->user->name]));

            $this->redirectRoute('admin.users.show', $this->user);
        }
    }

    public function sendLink(): void
    {
        $this->authorize('resetPassword', $this->user);

        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        $key = 'admin-reset-link|'.$this->user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->dispatch('toast', type: 'warning', message: __('A link was sent recently. Please wait a moment.'));

            return;
        }

        RateLimiter::hit($key, 300);
        Password::sendResetLink(['email' => $this->user->email]);

        $this->dispatch('toast', type: 'success', message: __('A password reset link was sent to :email.', ['email' => $this->user->email]));
    }

    public function render(): View
    {
        return view('livewire.admin.users.reset-password');
    }

    private function ensurePasswordConfirmed(): bool
    {
        if ($this->passwordRecentlyConfirmed()) {
            return true;
        }

        session()->put('url.intended', route('admin.users.reset-password', $this->user));
        $this->redirectRoute('password.confirm');

        return false;
    }
}
