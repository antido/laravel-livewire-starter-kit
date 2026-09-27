<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Reset password')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->string('email');
    }

    public function resetPassword(): void
    {
        try {
            /** @var array{token: string, email: string, password: string} $validated */
            $validated = $this->validate(ResetPasswordRequest::rules());
        } finally {
            $this->reset('password', 'password_confirmation');
        }

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // One message for unknown emails, wrong tokens and expired tokens.
            $this->addError('email', __('This password reset link is invalid or has expired.'));

            return;
        }

        session()->flash('success', __('Your password has been reset. You can now log in.'));

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
