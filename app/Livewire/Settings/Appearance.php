<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\ThemePreference;
use App\Http\Requests\Settings\UpdateAppearanceRequest;
use App\Models\User;
use App\Services\UserPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Appearance')]
class Appearance extends Component
{
    public string $theme = '';

    public function mount(): void
    {
        $this->theme = $this->user()->theme_preference->value;
    }

    public function save(UserPreferenceService $preferences): void
    {
        /** @var array{theme: string} $validated */
        $validated = $this->validate(UpdateAppearanceRequest::rules());

        $theme = ThemePreference::from($validated['theme']);

        // Always the signed-in user: there is no user id input.
        $preferences->setTheme($this->user(), $theme);

        $this->dispatch('theme-saved', theme: $theme->value);
        $this->dispatch('toast', type: 'success', message: __('Appearance saved.'));
    }

    public function render(): View
    {
        return view('livewire.settings.appearance', [
            'themes' => ThemePreference::cases(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
