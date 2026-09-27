<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Admin\Roles\Create;
use App\Livewire\Admin\Roles\Edit;
use App\Livewire\Admin\Roles\Index;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function owner(): User
    {
        return $this->userWithRole(RoleName::Owner);
    }

    /**
     * A user who can manage roles but holds only some permissions.
     */
    private function limitedRoleManager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            PermissionName::DashboardView->value,
            PermissionName::RolesView->value,
            PermissionName::RolesCreate->value,
            PermissionName::RolesUpdate->value,
            PermissionName::RolesDelete->value,
            PermissionName::UsersView->value,
        ]);

        return $user;
    }

    private function customRole(string $name = 'Night shift', array $permissions = ['dashboard.view']): Role
    {
        $role = Role::findOrCreate($name, 'web');
        $role->syncPermissions($permissions);

        return $role;
    }

    // ---- Access ----

    public function test_the_pages_open_with_permission(): void
    {
        $owner = $this->owner();
        $role = $this->customRole();

        $this->actingAs($owner)->get(route('admin.roles.index'))->assertOk()->assertSee('Night shift')->assertSee('Administrator');
        $this->actingAs($owner)->get(route('admin.roles.create'))->assertOk()->assertSee('Audit Trails')->assertSee('Privileges');
        $this->actingAs($owner)->get(route('admin.roles.edit', $role))->assertOk();
    }

    public function test_an_administrator_can_view_but_not_change_roles(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);
        $role = $this->customRole();

        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->assertDontSee('Add role');
        $this->actingAs($admin)->get(route('admin.roles.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.roles.edit', $role))->assertForbidden();
        Livewire::actingAs($admin)->test(Index::class)->call('delete', $role->id)->assertForbidden();
        $this->assertModelExists($role);
    }

    public function test_users_without_roles_view_get_403_on_routes_and_components(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $role = $this->customRole();

        $this->actingAs($staff)->get(route('admin.roles.index'))->assertForbidden();
        Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Create::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Edit::class, ['role' => $role])->assertForbidden();
    }

    // ---- Create ----

    public function test_an_authorized_user_can_create_a_role_with_permissions(): void
    {
        Livewire::actingAs($this->owner())->test(Create::class)
            ->set('name', 'Night shift')
            ->set('permissions', ['dashboard.view', 'employees.view'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Night shift');
        $this->assertEqualsCanonicalizing(['dashboard.view', 'employees.view'], $role->permissions->pluck('name')->all());
    }

    public function test_role_names_are_validated(): void
    {
        $this->customRole('Night shift');

        Livewire::actingAs($this->owner())->test(Create::class)
            ->set('name', 'Night shift')->call('save')->assertHasErrors(['name' => 'unique']);
        Livewire::actingAs($this->owner())->test(Create::class)
            ->set('name', 'Owner')->call('save')->assertHasErrors('name');
        Livewire::actingAs($this->owner())->test(Create::class)
            ->set('name', '<script>')->call('save')->assertHasErrors(['name' => 'regex']);
        Livewire::actingAs($this->owner())->test(Create::class)
            ->set('name', 'Valid')->set('permissions', ['not.a.permission'])->call('save')->assertHasErrors('permissions.0');
    }

    public function test_an_actor_cannot_grant_permissions_they_do_not_have(): void
    {
        $manager = $this->limitedRoleManager();

        Livewire::actingAs($manager)->test(Create::class)
            ->set('name', 'Escalator')
            ->set('permissions', ['dashboard.view', 'users.delete'])
            ->call('save')
            ->assertHasErrors('permissions');

        $this->assertNull(Role::where('name', 'Escalator')->first());

        Livewire::actingAs($manager)->test(Create::class)
            ->assertViewHas('groups', fn (array $groups): bool => collect($groups['users']['permissions'])->firstWhere('name', 'users.delete')['grantable'] === false);
    }

    public function test_select_all_toggles_only_grantable_permissions_of_a_module(): void
    {
        $manager = $this->limitedRoleManager();

        Livewire::actingAs($manager)->test(Create::class)
            ->call('toggleModule', 'users')
            ->assertSet('permissions', ['users.view'])
            ->call('toggleModule', 'users')
            ->assertSet('permissions', []);

        Livewire::actingAs($this->owner())->test(Create::class)
            ->call('toggleModule', 'employees')
            ->assertSet('permissions', ['employees.view', 'employees.create', 'employees.update', 'employees.delete']);
    }

    // ---- Update ----

    public function test_an_authorized_user_can_rename_a_custom_role_and_change_its_permissions(): void
    {
        $role = $this->customRole();

        Livewire::actingAs($this->owner())->test(Edit::class, ['role' => $role])
            ->set('name', 'Day shift')
            ->set('permissions', ['dashboard.view', 'users.view'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertSame('Day shift', $role->name);
        $this->assertEqualsCanonicalizing(['dashboard.view', 'users.view'], $role->permissions->pluck('name')->all());
    }

    public function test_system_roles_cannot_be_renamed_but_their_permissions_can_change(): void
    {
        $staff = Role::findByName(RoleName::User->value);

        Livewire::actingAs($this->owner())->test(Edit::class, ['role' => $staff])
            ->set('name', 'Helpers')
            ->call('save')
            ->assertDispatched('toast', type: 'danger', message: 'System roles cannot be renamed.');
        $this->assertSame(RoleName::User->value, $staff->fresh()?->name);

        Livewire::actingAs($this->owner())->test(Edit::class, ['role' => $staff])
            ->set('permissions', ['dashboard.view', 'employees.view'])
            ->call('save')
            ->assertHasNoErrors();
        $this->assertEqualsCanonicalizing(['dashboard.view', 'employees.view'], $staff->fresh()?->permissions->pluck('name')->all());
    }

    public function test_the_super_admin_role_permissions_are_not_editable(): void
    {
        $superAdminRole = Role::findByName(RoleName::SuperAdmin->value);

        // Nobody but a Super Admin even opens it.
        $this->actingAs($this->owner())->get(route('admin.roles.edit', $superAdminRole))->assertForbidden();

        // A Super Admin can open it, but not change it.
        Livewire::actingAs($this->superAdmin())->test(Edit::class, ['role' => $superAdminRole])
            ->set('permissions', ['dashboard.view'])
            ->call('save')
            ->assertDispatched('toast', type: 'danger');

        Livewire::actingAs($this->superAdmin())->test(Edit::class, ['role' => $superAdminRole])
            ->call('toggleModule', 'users')
            ->assertSet('permissions', []);

        $this->assertCount(0, $superAdminRole->fresh()?->permissions ?? []);
    }

    public function test_an_actor_cannot_add_or_remove_permissions_they_do_not_have(): void
    {
        $manager = $this->limitedRoleManager();
        $role = $this->customRole('Mixed', ['dashboard.view', 'audit_trails.view']);

        Livewire::actingAs($manager)->test(Edit::class, ['role' => $role])
            ->set('permissions', ['dashboard.view'])
            ->call('save')
            ->assertHasErrors('permissions');

        Livewire::actingAs($manager)->test(Edit::class, ['role' => $role])
            ->set('permissions', ['dashboard.view', 'audit_trails.view', 'employees.delete'])
            ->call('save')
            ->assertHasErrors('permissions');

        $this->assertEqualsCanonicalizing(['dashboard.view', 'audit_trails.view'], $role->fresh()?->permissions->pluck('name')->all());

        // Permissions they hold can still be changed, leaving the others untouched.
        Livewire::actingAs($manager)->test(Edit::class, ['role' => $role])
            ->set('permissions', ['dashboard.view', 'audit_trails.view', 'users.view'])
            ->call('save')
            ->assertHasNoErrors();
        $this->assertContains('users.view', $role->fresh()?->permissions->pluck('name')->all());
    }

    public function test_permission_changes_take_effect_immediately(): void
    {
        $role = $this->customRole('Night shift', ['dashboard.view']);
        $member = User::factory()->withRole('Night shift')->create();

        $this->assertFalse($member->fresh()?->can('employees.view'));

        Livewire::actingAs($this->owner())->test(Edit::class, ['role' => $role])
            ->set('permissions', ['dashboard.view', 'employees.view'])
            ->call('save');

        $this->assertTrue($member->fresh()?->can('employees.view'));
    }

    // ---- Delete ----

    public function test_a_custom_role_without_users_can_be_deleted(): void
    {
        $role = $this->customRole();

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('delete', $role->id)
            ->assertDispatched('toast', type: 'success');

        $this->assertModelMissing($role);
    }

    public function test_a_role_assigned_to_users_is_not_deleted(): void
    {
        $role = $this->customRole();
        User::factory()->count(2)->withRole('Night shift')->create();

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('delete', $role->id)
            ->assertDispatched('toast', type: 'danger', message: 'This role is assigned to 2 users. Remove it from them first.');

        $this->assertModelExists($role);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        foreach (RoleName::cases() as $name) {
            $role = Role::findByName($name->value);

            Livewire::actingAs($this->owner())->test(Index::class)->call('delete', $role->id)->assertForbidden();

            // Even a Super Admin, who passes the policy, is stopped by the service.
            Livewire::actingAs($this->superAdmin())->test(Index::class)->call('delete', $role->id)
                ->assertDispatched('toast', type: 'danger', message: 'System roles cannot be deleted.');

            $this->assertModelExists($role);
        }
    }

    public function test_the_list_searches_and_shows_counts(): void
    {
        $this->customRole('Night shift', ['dashboard.view', 'users.view']);

        Livewire::actingAs($this->owner())->test(Index::class)
            ->set('search', 'night')
            ->assertSee('Night shift')
            ->assertDontSee('Administrator')
            ->assertViewHas('roles', fn ($roles): bool => $roles->first()->permissions_count === 2);
    }

    public function test_permissions_are_grouped_by_module_with_custom_ones_last(): void
    {
        Permission::findOrCreate('inventory_counts.run', 'web');
        Permission::findOrCreate('users.export', 'web');

        $groups = app(RoleService::class)->permissionsByModule();

        $this->assertSame(['dashboard', 'users', 'roles', 'permissions', 'employees', 'audit_trails', 'inventory_counts'], array_keys($groups));
        $this->assertSame(['users.view', 'users.create', 'users.update', 'users.delete', 'users.export'], array_map(fn (Permission $p): string => $p->name, $groups['users']));
    }
}
