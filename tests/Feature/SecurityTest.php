<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Admin\AuditTrails\Index as AuditIndex;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Create as RoleCreate;
use App\Livewire\Admin\Roles\Edit as RoleEdit;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Users\Create as UserCreate;
use App\Livewire\Admin\Users\Edit as UserEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\ResetPassword;
use App\Livewire\Admin\Users\Show as UserShow;
use App\Livewire\Employees\Create as EmployeeCreate;
use App\Livewire\Employees\Edit as EmployeeEdit;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Employees\Show as EmployeeShow;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use RuntimeException;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    /**
     * Routes reachable without signing in, or that are part of the sign-in flow itself.
     */
    private const array PUBLIC_ROUTES = [
        'home', 'login', 'register', 'password.request', 'password.reset', 'verification.notice',
        'verification.verify', 'password.confirm', 'logout', 'dev.components', 'storage.local', 'storage.local.upload',
    ];

    /**
     * Signed-in pages that need no permission: each user only sees their own data there.
     */
    private const array PERSONAL_ROUTES = ['settings.appearance', 'notifications.index'];

    /**
     * @return list<RoutingRoute>
     */
    private function appRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (RoutingRoute $route): bool => $route->getName() !== null
                && ! str_starts_with((string) $route->getName(), 'livewire.')
                && ! str_starts_with((string) $route->getName(), 'sanctum.')
                && ! str_starts_with((string) $route->getName(), 'boost.')
                && ! in_array($route->uri(), ['up', 'api/user'], true)
                && ! in_array($route->getName(), self::PUBLIC_ROUTES, true),
        ));
    }

    // ---- Access control ----

    public function test_every_app_route_requires_auth_active_and_a_permission(): void
    {
        $routes = $this->appRoutes();
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $name = (string) $route->getName();

            $this->assertContains('auth', $middleware, "{$name} is missing auth.");
            $this->assertContains('active', $middleware, "{$name} is missing active.");

            if (! in_array($name, self::PERSONAL_ROUTES, true)) {
                $this->assertNotEmpty(
                    array_filter($middleware, fn ($m): bool => is_string($m) && str_starts_with($m, 'permission:')),
                    "{$name} has no permission middleware.",
                );
            }
        }
    }

    public function test_the_api_route_requires_sanctum_and_an_active_account(): void
    {
        $route = Route::getRoutes()->getByAction('Closure') ?? collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === 'api/user');
        $middleware = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === 'api/user')?->gatherMiddleware() ?? [];

        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('active', $middleware);
        $this->assertNotNull($route);

        $this->getJson('/api/user')->assertUnauthorized();

        $user = $this->userWithRole(RoleName::User);
        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $user->forceFill(['status' => 'inactive'])->save();
        $this->actingAs($user)->getJson('/api/user')->assertForbidden();
    }

    public function test_guests_are_redirected_from_every_protected_page(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create();
        $role = Role::findByName(RoleName::User->value);

        foreach ($this->appRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $url = route((string) $route->getName(), ['user' => $user, 'employee' => $employee, 'role' => $role]);

            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_a_user_without_permissions_gets_403_on_every_admin_and_employee_page(): void
    {
        $nobody = User::factory()->create();
        $user = User::factory()->create();
        $employee = Employee::factory()->create();
        $role = Role::findByName(RoleName::User->value);

        foreach ($this->appRoutes() as $route) {
            if (in_array($route->getName(), self::PERSONAL_ROUTES, true) || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $url = route((string) $route->getName(), ['user' => $user, 'employee' => $employee, 'role' => $role]);

            $this->actingAs($nobody)->get($url)->assertForbidden();
        }
    }

    public function test_every_admin_and_employee_livewire_action_refuses_a_user_without_permission(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $target = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->create();
        $archived = Employee::factory()->create();
        $archived->delete();
        $role = Role::findOrCreate('Night shift', 'web');
        $custom = Permission::findOrCreate('reports.run', 'web');

        $mounts = [
            [UsersIndex::class, []], [UserCreate::class, []], [UserShow::class, ['user' => $target]],
            [UserEdit::class, ['user' => $target]], [ResetPassword::class, ['user' => $target]],
            [RolesIndex::class, []], [RoleCreate::class, []], [RoleEdit::class, ['role' => $role]],
            [PermissionsIndex::class, []], [AuditIndex::class, []],
            [EmployeesIndex::class, []], [EmployeeCreate::class, []], [EmployeeShow::class, ['employee' => $employee]],
            [EmployeeEdit::class, ['employee' => $employee]],
        ];

        foreach ($mounts as [$component, $params]) {
            Livewire::actingAs($staff)->test($component, $params)->assertForbidden();
        }

        // Components that a user CAN open with view access must still refuse the write actions.
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['dashboard.view', 'users.view', 'roles.view', 'permissions.view', 'employees.view', 'audit_trails.view']);

        Livewire::actingAs($viewer)->test(UsersIndex::class)->call('toggleStatus', $target->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(UsersIndex::class)->call('delete', $target->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(RolesIndex::class)->call('delete', $role->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(PermissionsIndex::class)->call('create')->assertForbidden();
        Livewire::actingAs($viewer)->test(PermissionsIndex::class)->call('edit', $custom->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(PermissionsIndex::class)->call('delete', $custom->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(EmployeesIndex::class)->call('archive', $employee->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(EmployeesIndex::class)->call('restore', $archived->id)->assertForbidden();

        $this->assertModelExists($target);
        $this->assertModelExists($role);
        $this->assertModelExists($custom);
        $this->assertNotSoftDeleted($employee);
        $this->assertSoftDeleted($archived);
    }

    // ---- Crafted payloads ----

    public function test_crafted_role_and_ownership_fields_are_ignored_or_rejected(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        // A property the component does not have cannot be set.
        foreach (['is_admin', 'user_id', 'created_by', 'permissions'] as $field) {
            try {
                Livewire::actingAs($admin)->test(UserCreate::class)->set($field, 1);
                $this->fail("{$field} was accepted.");
            } catch (\Throwable) {
                // Expected.
            }
        }

        // A role outside the actor's permissions is rejected even when sent directly.
        Livewire::actingAs($admin)->test(UserCreate::class)
            ->set('name', 'X')->set('email', 'x@example.com')
            ->set('password', 'password-123')->set('password_confirmation', 'password-123')
            ->set('roles', [RoleName::Owner->value])->set('status', 'active')
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertNull(User::where('email', 'x@example.com')->first());
    }

    // ---- Headers, errors and exposure ----

    public function test_security_headers_are_sent_when_enabled(): void
    {
        config(['security.headers' => true]);

        $response = $this->get(route('login'))->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+' 'unsafe-eval'/", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'), 'HSTS is off unless enabled and on HTTPS.');

        // The inline theme script and Livewire's scripts carry the same nonce.
        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $content = (string) $response->getContent();
        preg_match_all('/<script[^>]*>/', $content, $scripts);
        $this->assertNotEmpty($scripts[0]);
        foreach ($scripts[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$matches[1].'"', $tag, "A script tag without the CSP nonce: {$tag}");
        }
    }

    public function test_error_pages_also_get_the_security_headers(): void
    {
        config(['security.headers' => true]);

        $response = $this->get('/this-page-does-not-exist')->assertNotFound();

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);
        $this->assertMatchesRegularExpression('/<script\s+nonce="'.preg_quote($matches[1], '/').'"/', (string) $response->getContent());
    }

    public function test_hsts_is_sent_only_on_https_when_enabled(): void
    {
        config(['security.headers' => true, 'security.hsts' => true]);

        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertNull($this->get('http://localhost/login')->headers->get('Strict-Transport-Security'));
    }

    public function test_security_headers_are_off_when_disabled(): void
    {
        config(['security.headers' => false]);

        $this->assertNull($this->get(route('login'))->headers->get('Content-Security-Policy'));
    }

    public function test_debug_off_hides_error_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/boom', fn () => throw new RuntimeException('secret-internal-detail at /var/www/app'))->middleware('web');

        $this->get('/_test/boom')
            ->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertDontSee('secret-internal-detail')
            ->assertDontSee('RuntimeException')
            ->assertDontSee(base_path());
    }

    public function test_error_pages_use_the_app_design(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found')->assertSee('window.appTheme', false);

        $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden()->assertSee('Access denied');
    }

    public function test_the_livewire_endpoint_is_rate_limited(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->getName() === 'livewire.update');

        $this->assertNotNull($route);
        $this->assertContains('throttle:livewire', $route->gatherMiddleware());
    }

    public function test_password_hashes_never_reach_rendered_pages(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->withRole(RoleName::User)->create();
        $employee = Employee::factory()->withAccount($target)->create();

        foreach ([route('dashboard'), route('admin.users.index'), route('admin.users.show', $target), route('admin.users.edit', $target), route('employees.show', $employee), route('admin.audit-trails.index')] as $url) {
            $content = (string) $this->actingAs($superAdmin)->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString((string) $target->password, $content, $url);
            $this->assertStringNotContainsString((string) $superAdmin->password, $content, $url);
            $this->assertStringNotContainsString('$2y$', $content, $url);
        }
    }

    public function test_session_cookies_are_http_only_and_same_site(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }
}
