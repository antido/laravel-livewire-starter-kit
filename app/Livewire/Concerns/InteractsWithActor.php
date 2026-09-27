<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\ActionNotAllowedException;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Helpers for Livewire components that act on behalf of the signed-in user.
 */
trait InteractsWithActor
{
    protected function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Run an action; when a business rule forbids it, show the reason as an error toast instead of failing.
     *
     * @param  Closure(): mixed  $action
     */
    protected function attempt(Closure $action, ?string $success = null): bool
    {
        try {
            $action();
        } catch (ActionNotAllowedException $exception) {
            $this->dispatch('toast', type: 'danger', message: $exception->getMessage());

            return false;
        }

        if ($success !== null) {
            $this->dispatch('toast', type: 'success', message: $success);
        }

        return true;
    }

    /**
     * Whether the actor confirmed their password recently (Laravel's password.confirm timeout).
     */
    protected function passwordRecentlyConfirmed(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 10800);

        return time() - $confirmedAt < $timeout;
    }
}
