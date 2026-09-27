<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    /**
     * Reset-link requests allowed per IP address per minute.
     */
    public const int MAX_REQUESTS = 5;

    public const string SENT_MESSAGE = 'If an account exists for that email address, we have sent a password reset link.';

    public string $email = '';

    public ?string $status = null;

    public function sendResetLink(): void
    {
        $this->validate(ForgotPasswordRequest::rules());

        $key = 'forgot-password|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            throw ValidationException::withMessages([
                'email' => __('Too many requests. Please try again in a minute.'),
            ]);
        }

        RateLimiter::hit($key);

        // The broker's result is ignored on purpose: the answer is the same whether or not the address has an account.
        Password::sendResetLink(['email' => $this->email]);

        $this->reset('email');
        $this->status = __(self::SENT_MESSAGE);
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
