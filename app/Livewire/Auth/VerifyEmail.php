<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Verify email')]
class VerifyEmail extends Component
{
    /**
     * Verification emails allowed per user per minute.
     */
    public const int MAX_RESENDS = 6;

    public ?string $status = null;

    public function mount(): void
    {
        if (Auth::user()?->hasVerifiedEmail()) {
            $this->redirectIntended(route('dashboard', absolute: false));
        }
    }

    public function resend(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->hasVerifiedEmail()) {
            $this->redirectIntended(route('dashboard', absolute: false));

            return;
        }

        $key = 'verify-email|'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_RESENDS)) {
            $this->status = __('Please wait a moment before requesting another link.');

            return;
        }

        RateLimiter::hit($key);

        $user->sendEmailVerificationNotification();

        $this->status = __('A new verification link has been sent to your email address.');
    }

    public function logout(AuthenticationService $authentication): void
    {
        $authentication->logout();

        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email');
    }
}
