<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private const array OUT_OF_SCOPE = ['Sales', 'Inventory', 'Suppliers', 'Purchases', 'Expenses', 'Payroll', 'Reports'];

    /**
     * Stand-in routes for modules built in later tasks, so their menu items can be tested now.
     */
    private function registerModuleRoutes(): void
    {
        foreach (['employees.index' => 'employees', 'admin.users.index' => 'admin/users', 'admin.roles.index' => 'admin/roles', 'admin.permissions.index' => 'admin/permissions', 'admin.audit-trails.index' => 'admin/audit-trails'] as $name => $uri) {
            if (! Route::has($name)) {
                Route::get($uri, fn () => 'ok')->name($name);
            }
        }

        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * @return list<string>
     */
    private function menuLabels(User $user): array
    {
        return array_column(app(NavigationService::class)->itemsFor($user), 'label');
    }

    public function test_a_user_with_only_the_dashboard_permission_sees_no_administration_group(): void
    {
        $this->registerModuleRoutes();
        $staff = $this->userWithRole(RoleName::User);

        $this->assertSame(['Dashboard'], $this->menuLabels($staff));
        $this->assertSame([null], array_column(app(NavigationService::class)->sectionsFor($staff), 'label'));

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('Administration');
    }

    public function test_a_user_with_users_view_sees_administration_users_only(): void
    {
        $this->registerModuleRoutes();
        $user = User::factory()->create();
        $user->givePermissionTo([PermissionName::DashboardView->value, PermissionName::UsersView->value]);

        $sections = app(NavigationService::class)->sectionsFor($user);

        $this->assertSame([null, 'Administration'], array_column($sections, 'label'));
        $this->assertSame(['Users'], array_column($sections[1]['items'], 'label'));
    }

    public function test_the_super_admin_sees_every_item_whose_route_exists(): void
    {
        $superAdmin = $this->superAdmin();

        $this->registerModuleRoutes();
        $this->assertSame(['Dashboard', 'Employees', 'Users', 'Roles', 'Privileges', 'Audit Trails'], $this->menuLabels($superAdmin));
    }

    public function test_items_whose_route_does_not_exist_are_hidden(): void
    {
        config(['navigation.sections.0.items.1.route' => 'a.route.that.does.not.exist']);

        $this->assertNotContains('Employees', $this->menuLabels($this->superAdmin()));
    }

    public function test_the_sidebar_renders_the_permitted_items_and_marks_the_active_one(): void
    {
        $this->registerModuleRoutes();

        $this->actingAs($this->userWithRole(RoleName::Administrator))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administration')
            ->assertSee('Audit Trails')
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_no_out_of_scope_module_appears_in_the_menu(): void
    {
        $this->registerModuleRoutes();

        $response = $this->actingAs($this->superAdmin())->get(route('dashboard'))->assertOk();

        foreach (self::OUT_OF_SCOPE as $module) {
            $response->assertDontSee($module);
        }

        $labels = array_column(array_merge(...array_column(config('navigation.sections'), 'items')), 'label');
        $this->assertEmpty(array_intersect($labels, self::OUT_OF_SCOPE));
    }

    public function test_the_header_shows_the_user_menu_with_a_post_logout_form(): void
    {
        $user = $this->userWithRole(RoleName::User);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Open menu');
    }

    public function test_logout_from_the_user_menu_requires_post(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $this->actingAs($user);

        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }
}
