<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Confirm password')]
class ConfirmPassword extends Component
{
    /**
     * Confirmation attempts allowed per user per minute.
     */
    public const int MAX_ATTEMPTS = 5;

    public string $password = '';

    public function confirm(AuthenticationService $authentication): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $key = 'confirm-password|'.$user->getKey();

        try {
            /** @var array{password: string} $validated */
            $validated = $this->validate(ConfirmPasswordRequest::rules());
        } finally {
            $this->reset('password');
        }

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'password' => __('Too many attempts. Please try again in a minute.'),
            ]);
        }

        RateLimiter::hit($key);

        $authentication->confirmPassword($user, $validated['password']);

        RateLimiter::clear($key);

        $this->redirectIntended(route('dashboard', absolute: false));
    }

    public function render(): View
    {
        return view('livewire.auth.confirm-password');
    }
}
