<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_the_seeder_creates_every_role_and_permission(): void
    {
        $this->assertEqualsCanonicalizing(RoleName::values(), Role::pluck('name')->all());
        $this->assertEqualsCanonicalizing(PermissionName::values(), Permission::pluck('name')->all());
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(count(RoleName::cases()), Role::count());
        $this->assertSame(count(PermissionName::cases()), Permission::count());
        $this->assertSame(
            count(RoleName::Owner->defaultPermissions()),
            Role::findByName(RoleName::Owner->value)->permissions()->count(),
        );
    }

    public function test_each_role_has_exactly_its_mapped_permissions(): void
    {
        $expected = [
            RoleName::SuperAdmin->value => [],
            RoleName::Owner->value => PermissionName::values(),
            RoleName::Administrator->value => [
                'dashboard.view',
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view',
                'permissions.view',
                'employees.view', 'employees.create', 'employees.update', 'employees.delete',
                'audit_trails.view',
            ],
            RoleName::User->value => ['dashboard.view'],
        ];

        foreach ($expected as $role => $permissions) {
            $this->assertEqualsCanonicalizing(
                $permissions,
                Role::findByName($role)->permissions->pluck('name')->all(),
                "The {$role} role has the wrong permissions.",
            );
        }
    }

    public function test_the_super_admin_passes_every_gate_check(): void
    {
        $superAdmin = $this->superAdmin();

        foreach (PermissionName::cases() as $permission) {
            $this->assertTrue(Gate::forUser($superAdmin)->allows($permission->value));
        }

        $this->assertTrue(Gate::forUser($superAdmin)->allows('an-ability-nobody-defined'));
    }

    public function test_other_roles_are_limited_to_their_permissions(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $administrator = $this->userWithRole(RoleName::Administrator);

        $this->assertTrue($staff->can('dashboard.view'));
        $this->assertFalse($staff->can('users.view'));
        $this->assertTrue($administrator->can('users.delete'));
        $this->assertFalse($administrator->can('roles.update'));
    }

    public function test_no_out_of_scope_module_permissions_exist(): void
    {
        $modules = ['sales', 'inventory', 'products', 'suppliers', 'purchases', 'expenses', 'payroll', 'reports'];

        foreach (Permission::pluck('name') as $name) {
            $this->assertNotContains(PermissionName::moduleOf($name), $modules);
        }
    }

    public function test_permissions_are_grouped_by_module(): void
    {
        $groups = PermissionName::grouped();

        $this->assertSame(['dashboard', 'users', 'roles', 'permissions', 'employees', 'audit_trails'], array_keys($groups));
        $this->assertCount(4, $groups['users']);
        $this->assertSame('Privileges', PermissionName::moduleLabel('permissions'));
        $this->assertSame('Audit Trails', PermissionName::moduleLabel('audit_trails'));
    }

    public function test_the_admin_user_seeder_fails_clearly_when_the_env_credentials_are_missing(): void
    {
        config(['app.super_admin' => ['name' => 'Root', 'email' => null, 'password' => '']]);

        try {
            $this->seed(AdminUserSeeder::class);
            $this->fail('The seeder should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SUPER_ADMIN_EMAIL, SUPER_ADMIN_PASSWORD', $exception->getMessage());
            $this->assertStringNotContainsString('SUPER_ADMIN_NAME', $exception->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_the_admin_user_seeder_creates_the_super_admin_from_the_environment(): void
    {
        config(['app.super_admin' => ['name' => 'Root Admin', 'email' => 'Root@Example.com', 'password' => 'a-long-secret-1']]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $user = User::sole();
        $this->assertSame('root@example.com', $user->email);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isActive());
        $this->assertTrue(Hash::check('a-long-secret-1', $user->getAuthPassword()));
    }

    public function test_the_create_super_admin_command_creates_a_super_admin_with_a_hashed_password(): void
    {
        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Name', 'Maria Santos')
            ->expectsQuestion('Email address', 'maria@example.com')
            ->expectsQuestion('Password', 'correct-horse-9')
            ->expectsQuestion('Confirm password', 'correct-horse-9')
            ->assertSuccessful();

        $user = User::where('email', 'maria@example.com')->sole();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNotSame('correct-horse-9', $user->getAuthPassword());
        $this->assertTrue(Hash::check('correct-horse-9', $user->getAuthPassword()));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_the_create_super_admin_command_rejects_mismatched_passwords_and_bad_emails(): void
    {
        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Name', 'Maria Santos')
            ->expectsQuestion('Email address', 'maria@example.com')
            ->expectsQuestion('Password', 'correct-horse-9')
            ->expectsQuestion('Confirm password', 'something-else')
            ->assertFailed();

        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Name', 'Maria Santos')
            ->expectsQuestion('Email address', 'not-an-email')
            ->expectsQuestion('Password', 'correct-horse-9')
            ->expectsQuestion('Confirm password', 'correct-horse-9')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_only_a_super_admin_can_assign_the_super_admin_role(): void
    {
        $roles = app(RoleService::class);
        $superAdminRole = Role::findByName(RoleName::SuperAdmin->value);

        $this->assertTrue($roles->canAssign($this->superAdmin(), $superAdminRole));
        $this->assertFalse($roles->canAssign($this->userWithRole(RoleName::Owner), $superAdminRole));
    }

    public function test_a_non_super_admin_can_assign_only_roles_within_their_own_permissions(): void
    {
        $roles = app(RoleService::class);
        $administrator = $this->userWithRole(RoleName::Administrator);

        $this->assertTrue($roles->canAssign($administrator, Role::findByName(RoleName::User->value)));
        $this->assertTrue($roles->canAssign($administrator, Role::findByName(RoleName::Administrator->value)));
        $this->assertFalse($roles->canAssign($administrator, Role::findByName(RoleName::Owner->value)));

        $this->assertTrue($roles->canGrant($administrator, 'users.view'));
        $this->assertFalse($roles->canGrant($administrator, 'roles.delete'));
        $this->assertEqualsCanonicalizing(
            [RoleName::Administrator->value, RoleName::User->value],
            $roles->assignableRoles($administrator)->pluck('name')->all(),
        );
    }

    public function test_the_last_active_super_admin_is_detected(): void
    {
        $roles = app(RoleService::class);
        $first = $this->superAdmin();

        $this->assertTrue($roles->isLastActiveSuperAdmin($first));

        $second = $this->superAdmin();
        $this->assertFalse($roles->isLastActiveSuperAdmin($first));

        $second->forceFill(['status' => UserStatus::Inactive])->save();
        $this->assertTrue($roles->isLastActiveSuperAdmin($first));
        $this->assertFalse($roles->isLastActiveSuperAdmin($this->userWithRole(RoleName::Owner)));
    }

    public function test_there_is_no_cashier_role(): void
    {
        $this->assertNull(RoleName::tryFrom('Cashier'));
        $this->assertNull(Role::where('name', 'Cashier')->first());
    }

    public function test_older_databases_move_cashiers_to_staff_then_rename_staff_to_user(): void
    {
        // An older database: the role was still called Staff, and a Cashier role existed.
        Role::findByName(RoleName::User->value)->update(['name' => 'Staff']);
        $formerStaff = User::factory()->withRole('Staff')->create();
        $cashier = Role::findOrCreate('Cashier', 'web');
        $cashier->givePermissionTo('dashboard.view');
        $formerCashier = User::factory()->withRole('Cashier')->create();

        (require database_path('migrations/2026_09_27_213814_remove_cashier_role.php'))->up();
        (require database_path('migrations/2026_09_27_214236_rename_staff_role_to_user.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertNull(Role::where('name', 'Cashier')->first());
        $this->assertNull(Role::where('name', 'Staff')->first());
        $this->assertSame([RoleName::User->value], $formerStaff->fresh()?->getRoleNames()->all());
        $this->assertSame([RoleName::User->value], $formerCashier->fresh()?->getRoleNames()->all());
        $this->assertTrue($formerCashier->fresh()?->can('dashboard.view'));
    }

    public function test_the_spatie_middleware_aliases_are_registered(): void
    {
        $aliases = Route::getMiddleware();

        $this->assertArrayHasKey('role', $aliases);
        $this->assertArrayHasKey('permission', $aliases);
        $this->assertArrayHasKey('role_or_permission', $aliases);
    }
}
