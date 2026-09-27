<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Dashboard\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function registerModuleRoutes(): void
    {
        foreach (['employees.index' => 'employees', 'admin.users.index' => 'admin/users', 'admin.audit-trails.index' => 'admin/audit-trails'] as $name => $uri) {
            if (! Route::has($name)) {
                Route::get($uri, fn () => 'ok')->name($name);
            }
        }

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_a_signed_in_user_sees_their_first_name_in_the_greeting(): void
    {
        $user = User::factory()->withRole(RoleName::User)->create(['name' => 'Juan Dela Cruz']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Dashboard::class)
            ->assertSee('Juan');

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertViewHas('roles', fn ($roles): bool => $roles->all() === [RoleName::User->value]);
    }

    public function test_the_greeting_follows_the_time_of_day_in_the_app_timezone(): void
    {
        $user = $this->userWithRole(RoleName::User);

        foreach (['08:00' => 'Good morning', '14:00' => 'Good afternoon', '20:00' => 'Good evening'] as $time => $greeting) {
            $this->travelTo(now(config('app.timezone'))->setTimeFromTimeString($time));

            Livewire::actingAs($user)->test(Dashboard::class)->assertSee($greeting);
        }
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_dashboard_permission_gets_403(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
    }

    public function test_quick_links_show_only_permitted_items(): void
    {
        $this->registerModuleRoutes();

        // User: only Settings.
        Livewire::actingAs($this->userWithRole(RoleName::User))->test(Dashboard::class)
            ->assertViewHas('quickLinks', fn (array $links): bool => array_column($links, 'label') === ['Settings']);

        // Administrator: every quick link.
        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(Dashboard::class)
            ->assertViewHas('quickLinks', fn (array $links): bool => array_column($links, 'label') === ['Employees', 'Users', 'Audit Trails', 'Settings']);
    }

    public function test_quick_links_are_hidden_until_their_routes_exist(): void
    {
        config(['navigation.sections.0.items.1.route' => 'a.route.that.does.not.exist']);

        Livewire::actingAs($this->superAdmin())->test(Dashboard::class)
            ->assertViewHas('quickLinks', fn (array $links): bool => ! in_array('Employees', array_column($links, 'label'), true));
    }

    public function test_the_page_has_no_kpi_or_analytics_placeholders(): void
    {
        $content = strtolower(strip_tags($this->actingAs($this->superAdmin())->get(route('dashboard'))->getContent() ?: ''));

        foreach (['kpi', 'analytics', 'revenue', 'chart', 'total sales'] as $word) {
            $this->assertStringNotContainsString($word, $content);
        }
    }
}
