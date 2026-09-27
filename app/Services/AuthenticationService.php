<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Thin wrapper around Laravel's session guard: throttling, the inactive-account rule and session hygiene.
 */
class AuthenticationService
{
    /**
     * Failed login attempts allowed per email + IP address per minute.
     */
    public const int MAX_LOGIN_ATTEMPTS = 5;

    public const string INACTIVE_MESSAGE = 'Your account is inactive. Please contact an administrator.';

    /**
     * Log in with Laravel's guard. Bad credentials get one generic message that never reveals whether
     * the account exists. Correct credentials for an inactive account are refused without signing in.
     *
     * @throws ValidationException
     */
    public function attempt(string $email, string $password, bool $remember, string $ip): User
    {
        $key = $this->throttleKey($email, $ip);

        $this->ensureIsNotRateLimited($key);

        $inactive = false;

        $authenticated = Auth::attemptWhen(
            ['email' => $email, 'password' => $password],
            function (User $user) use (&$inactive): bool {
                $inactive = ! $user->isActive();

                return ! $inactive;
            },
            $remember,
        );

        if ($inactive) {
            RateLimiter::clear($key);

            throw ValidationException::withMessages(['email' => __(self::INACTIVE_MESSAGE)]);
        }

        if (! $authenticated) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

        // A fresh session ID after login prevents session fixation.
        session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $user;
    }

    /**
     * Log out, invalidate the session and issue a new CSRF token.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Re-check the signed-in user's password before a sensitive action.
     *
     * @throws ValidationException
     */
    public function confirmPassword(User $user, string $password): void
    {
        if (! Auth::guard('web')->validate(['email' => $user->email, 'password' => $password])) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        session()->passwordConfirmed();
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $key): void
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(string $email, string $ip): string
    {
        return 'login|'.Str::transliterate(Str::lower($email).'|'.$ip);
    }
}
