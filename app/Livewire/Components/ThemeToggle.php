<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Enums\ThemePreference;
use App\Http\Requests\Settings\UpdateAppearanceRequest;
use App\Models\User;
use App\Services\UserPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * The header theme button for signed-in users: it switches the theme in the browser and saves it to the account.
 */
class ThemeToggle extends Component
{
    public function save(string $theme): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        /** @var array{theme: string} $validated */
        $validated = Validator::make(['theme' => $theme], UpdateAppearanceRequest::rules())->validate();

        app(UserPreferenceService::class)->setTheme($user, ThemePreference::from($validated['theme']));
    }

    public function render(): View
    {
        return view('livewire.components.theme-toggle');
    }
}
