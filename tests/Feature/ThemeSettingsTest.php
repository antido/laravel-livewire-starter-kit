<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\ThemePreference;
use App\Livewire\Components\ThemeToggle;
use App\Livewire\Settings\Appearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    /**
     * @return array<string, array{string}>
     */
    public static function themes(): array
    {
        return ['light' => ['light'], 'dark' => ['dark'], 'system' => ['system']];
    }

    public function test_new_users_follow_the_system_theme(): void
    {
        $this->assertSame(ThemePreference::System, User::factory()->create()->fresh()?->theme_preference);
    }

    public function test_the_appearance_page_renders_for_any_signed_in_user(): void
    {
        $this->actingAs($this->userWithRole(RoleName::User))
            ->get(route('settings.appearance'))
            ->assertOk()
            ->assertSeeLivewire(Appearance::class)
            ->assertSee('Light')
            ->assertSee('Dark')
            ->assertSee('System');
    }

    public function test_guests_cannot_open_the_appearance_page(): void
    {
        $this->get(route('settings.appearance'))->assertRedirect(route('login'));
    }

    #[DataProvider('themes')]
    public function test_a_user_can_save_each_theme_on_the_appearance_page(string $theme): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Appearance::class)
            ->set('theme', $theme)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('theme-saved', theme: $theme)
            ->assertDispatched('toast');

        $this->assertSame($theme, $user->fresh()?->theme_preference->value);
    }

    #[DataProvider('themes')]
    public function test_the_header_toggle_saves_each_theme(string $theme): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(ThemeToggle::class)->call('save', $theme)->assertHasNoErrors();

        $this->assertSame($theme, $user->fresh()?->theme_preference->value);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Appearance::class)
            ->set('theme', 'purple')
            ->call('save')
            ->assertHasErrors(['theme']);

        Livewire::actingAs($user)->test(Appearance::class)
            ->set('theme', '')
            ->call('save')
            ->assertHasErrors(['theme' => 'required']);

        Livewire::actingAs($user)->test(ThemeToggle::class)
            ->call('save', '<script>')
            ->assertHasErrors(['theme']);

        $this->assertSame(ThemePreference::System, $user->fresh()?->theme_preference);
    }

    public function test_a_user_can_only_change_their_own_theme(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $other = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(ThemeToggle::class)->call('save', 'dark', $other->id);

        $this->assertSame(ThemePreference::Dark, $user->fresh()?->theme_preference);
        $this->assertSame(ThemePreference::System, $other->fresh()?->theme_preference);

        // The Appearance page has no user id input to tamper with.
        $this->expectException(\Throwable::class);
        Livewire::actingAs($user)->test(Appearance::class)->set('userId', $other->id);
    }

    public function test_guests_cannot_call_the_toggle(): void
    {
        Livewire::test(ThemeToggle::class)->call('save', 'dark')->assertForbidden();
    }

    public function test_the_saved_preference_is_rendered_into_the_layout(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $user->forceFill(['theme_preference' => ThemePreference::Dark])->save();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-preference="dark"', false)
            ->assertSee('window.appTheme', false)
            ->assertSeeLivewire(ThemeToggle::class);
    }

    public function test_guests_load_pages_without_a_stored_preference(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-theme-preference', false)
            ->assertSee('window.appTheme', false)
            ->assertSee('data-test="theme-toggle"', false);
    }
}
