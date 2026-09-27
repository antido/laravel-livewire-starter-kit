<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

#[Layout('layouts::guest')]
#[Title('Sign up')]
class Register extends Component
{
    /**
     * Sign-ups allowed per IP address per ten minutes.
     */
    public const int MAX_REGISTRATIONS = 5;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->ensureRegistrationIsEnabled();
    }

    public function register(UserService $users): void
    {
        // Livewire actions post to their own endpoint, so the route middleware alone is not enough.
        $this->ensureRegistrationIsEnabled();
        $this->ensureIsNotRateLimited();

        try {
            /** @var array{name: string, email: string, password: string} $validated */
            $validated = $this->validate(RegisterRequest::rules());
        } finally {
            $this->reset('password', 'password_confirmation');
        }

        RateLimiter::hit($this->throttleKey(), 600);

        $user = $users->register($validated);

        Auth::guard('web')->login($user);
        session()->regenerate();

        $this->redirect(route('dashboard', absolute: false));
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }

    private function ensureRegistrationIsEnabled(): void
    {
        abort_unless((bool) config('auth.registration.enabled'), Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_REGISTRATIONS)) {
            $this->reset('password', 'password_confirmation');

            throw ValidationException::withMessages([
                'email' => __('Too many sign-up attempts. Please try again later.'),
            ]);
        }
    }

    private function throttleKey(): string
    {
        return 'register|'.request()->ip();
    }
}
