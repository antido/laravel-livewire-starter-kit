<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    /**
     * The breadcrumb labels on a page, in order.
     *
     * @return list<string>
     */
    private function crumbs(TestResponse $response): array
    {
        $content = (string) $response->assertOk()->getContent();
        preg_match('#<nav aria-label="Breadcrumb".*?</nav>#s', $content, $nav);
        $this->assertNotEmpty($nav, 'The page has no breadcrumbs.');

        preg_match_all('#<(?:a|span)[^>]*>\s*([^<]+?)\s*</(?:a|span)>#', $nav[0], $labels);

        return array_map(fn (string $label): string => html_entity_decode($label), $labels[1]);
    }

    public function test_every_signed_in_page_has_the_right_trail(): void
    {
        $superAdmin = $this->superAdmin();
        $employee = Employee::factory()->create(['first_name' => 'Rosa', 'last_name' => 'Santos', 'middle_name' => null, 'suffix' => null]);
        $user = User::factory()->withRole(RoleName::User)->create(['name' => 'Ana Reyes']);
        $role = Role::findByName(RoleName::Administrator->value);

        $expected = [
            route('dashboard') => ['Dashboard'],
            route('employees.index') => ['Dashboard', 'Employees'],
            route('employees.create') => ['Dashboard', 'Employees', 'Add employee'],
            route('employees.show', $employee) => ['Dashboard', 'Employees', 'Rosa Santos'],
            route('employees.edit', $employee) => ['Dashboard', 'Employees', 'Rosa Santos', 'Edit'],
            route('admin.users.index') => ['Dashboard', 'Administration', 'Users'],
            route('admin.users.create') => ['Dashboard', 'Administration', 'Users', 'Add user'],
            route('admin.users.show', $user) => ['Dashboard', 'Administration', 'Users', 'Ana Reyes'],
            route('admin.users.edit', $user) => ['Dashboard', 'Administration', 'Users', 'Ana Reyes', 'Edit'],
            route('admin.roles.index') => ['Dashboard', 'Administration', 'Roles'],
            route('admin.roles.create') => ['Dashboard', 'Administration', 'Roles', 'Add role'],
            route('admin.roles.edit', $role) => ['Dashboard', 'Administration', 'Roles', 'Edit Administrator'],
            route('admin.permissions.index') => ['Dashboard', 'Administration', 'Privileges'],
            route('admin.audit-trails.index') => ['Dashboard', 'Administration', 'Audit Trails'],
            route('notifications.index') => ['Dashboard', 'Notifications'],
            route('settings.appearance') => ['Dashboard', 'Settings', 'Appearance'],
        ];

        foreach ($expected as $url => $trail) {
            $this->assertSame($trail, $this->crumbs($this->actingAs($superAdmin)->get($url)), $url);
        }

        session()->put('auth.password_confirmed_at', time());
        $this->assertSame(
            ['Dashboard', 'Administration', 'Users', 'Ana Reyes', 'Reset password'],
            $this->crumbs($this->actingAs($superAdmin)->get(route('admin.users.reset-password', $user))),
        );
    }

    public function test_parent_crumbs_link_and_the_current_page_does_not(): void
    {
        $employee = Employee::factory()->create();

        $content = (string) $this->actingAs($this->superAdmin())->get(route('employees.edit', $employee))->getContent();

        $this->assertStringContainsString('<a href="'.route('dashboard').'"', $content);
        $this->assertStringContainsString('<a href="'.route('employees.index').'"', $content);
        $this->assertStringContainsString('<a href="'.route('employees.show', $employee).'"', $content);
        $this->assertMatchesRegularExpression('#<span aria-current="page"[^>]*>\s*Edit\s*</span>#', $content);
    }

    public function test_crumbs_do_not_link_to_pages_the_user_cannot_open(): void
    {
        // Can edit employees, but cannot see the list of users.
        $manager = User::factory()->create();
        $manager->givePermissionTo([PermissionName::DashboardView->value, PermissionName::EmployeesView->value]);

        $content = (string) $this->actingAs($manager)->get(route('employees.index'))->getContent();
        $this->assertStringContainsString('aria-label="Breadcrumb"', $content);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo([PermissionName::DashboardView->value, PermissionName::AuditTrailsView->value]);
        $content = (string) $this->actingAs($viewer)->get(route('admin.audit-trails.index'))->getContent();

        preg_match('#<nav aria-label="Breadcrumb".*?</nav>#s', $content, $nav);
        $this->assertStringNotContainsString('href="'.route('admin.users.index').'"', $nav[0]);
        $this->assertStringContainsString('href="'.route('dashboard').'"', $nav[0]);
    }

    public function test_archived_employees_keep_their_name_in_the_trail(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Old', 'last_name' => 'Timer', 'middle_name' => null, 'suffix' => null]);
        $employee->delete();

        $this->assertSame(['Dashboard', 'Employees', 'Old Timer'], $this->crumbs($this->actingAs($this->superAdmin())->get(route('employees.show', $employee))));
    }

    public function test_names_in_the_trail_are_escaped(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

        $content = (string) $this->actingAs($this->superAdmin())->get(route('admin.users.show', $user))->getContent();

        preg_match('#<nav aria-label="Breadcrumb".*?</nav>#s', $content, $nav);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $nav[0]);
        $this->assertStringContainsString('&lt;script&gt;', $nav[0]);
    }

    public function test_guest_pages_have_no_breadcrumbs(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('aria-label="Breadcrumb"', false);
        $this->get('/')->assertOk()->assertDontSee('aria-label="Breadcrumb"', false);
    }
}
