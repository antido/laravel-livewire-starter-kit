<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ThemePreference;
use App\Models\User;

class UserPreferenceService
{
    /**
     * Save a user's theme. Callers pass the signed-in user; the user is never taken from the request.
     */
    public function setTheme(User $user, ThemePreference $theme): void
    {
        $user->forceFill(['theme_preference' => $theme])->save();
    }
}
