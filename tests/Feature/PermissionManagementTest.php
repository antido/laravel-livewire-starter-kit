<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Admin\Permissions\Index;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function owner(): User
    {
        return $this->userWithRole(RoleName::Owner);
    }

    public function test_the_page_lists_permissions_grouped_by_module_with_their_roles(): void
    {
        $this->actingAs($this->owner())->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class)
            ->assertSeeInOrder(['Dashboard', 'Users', 'users.view', 'users.delete', 'Roles', 'Privileges', 'Employees', 'Audit Trails', 'audit_trails.view'])
            ->assertSee('Administrator');
    }

    public function test_search_filters_the_list(): void
    {
        Livewire::actingAs($this->owner())->test(Index::class)
            ->set('search', 'audit')
            ->assertSee('audit_trails.view')
            ->assertDontSee('users.view');
    }

    public function test_a_user_without_permissions_view_gets_403(): void
    {
        $staff = $this->userWithRole(RoleName::User);

        $this->actingAs($staff)->get(route('admin.permissions.index'))->assertForbidden();
        Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
    }

    public function test_an_authorized_user_can_create_a_custom_permission(): void
    {
        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'reports_export.run')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('toast', type: 'success');

        $this->assertNotNull(Permission::where('name', 'reports_export.run')->first());
    }

    public function test_the_name_format_and_uniqueness_are_validated(): void
    {
        foreach (['Users.View', 'users', 'users.view.all', 'users-view.x', 'users.', '.view', 'users.v1ew', '<b>x</b>.y'] as $invalid) {
            Livewire::actingAs($this->owner())->test(Index::class)
                ->call('create')
                ->set('name', $invalid)
                ->call('save')
                ->assertHasErrors('name');
        }

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('create')
            ->set('name', 'users.view')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);

        $this->assertSame(count(PermissionName::cases()), Permission::count());
    }

    public function test_a_custom_permission_can_be_renamed(): void
    {
        $permission = Permission::findOrCreate('reports.run', 'web');

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('edit', $permission->id)
            ->assertSet('name', 'reports.run')
            ->set('name', 'reports.execute')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('reports.execute', $permission->fresh()?->name);
    }

    public function test_system_permissions_cannot_be_renamed_or_deleted(): void
    {
        $system = Permission::where('name', PermissionName::UsersView->value)->sole();

        Livewire::actingAs($this->owner())->test(Index::class)->call('edit', $system->id)->assertForbidden();
        Livewire::actingAs($this->owner())->test(Index::class)->call('delete', $system->id)->assertForbidden();

        // A Super Admin passes the policy, but the service still refuses.
        Livewire::actingAs($this->superAdmin())->test(Index::class)->call('delete', $system->id)
            ->assertDispatched('toast', type: 'danger', message: 'System permissions cannot be deleted.');

        $this->assertModelExists($system);
        $this->assertSame(PermissionName::UsersView->value, $system->fresh()?->name);
    }

    public function test_the_locked_editing_id_cannot_be_forged_to_rename_a_system_permission(): void
    {
        $system = Permission::where('name', PermissionName::UsersView->value)->sole();

        $this->expectException(\Throwable::class);

        Livewire::actingAs($this->owner())->test(Index::class)->set('editingId', $system->id);
    }

    public function test_a_custom_permission_without_roles_can_be_deleted(): void
    {
        $permission = Permission::findOrCreate('reports.run', 'web');

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('delete', $permission->id)
            ->assertDispatched('toast', type: 'success');

        $this->assertModelMissing($permission);
    }

    public function test_a_custom_permission_used_by_a_role_is_not_deleted(): void
    {
        $permission = Permission::findOrCreate('reports.run', 'web');
        Role::findByName(RoleName::Owner->value)->givePermissionTo($permission);

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('delete', $permission->id)
            ->assertDispatched('toast', type: 'danger', message: 'This privilege is used by Owner. Remove it from those roles first.');

        $this->assertModelExists($permission);
    }

    public function test_actions_are_refused_without_the_matching_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([PermissionName::DashboardView->value, PermissionName::PermissionsView->value]);
        $custom = Permission::findOrCreate('reports.run', 'web');

        Livewire::actingAs($viewer)->test(Index::class)->call('create')->assertForbidden();
        Livewire::actingAs($viewer)->test(Index::class)->call('edit', $custom->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(Index::class)->call('delete', $custom->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(Index::class)->set('name', 'sneaky.add')->call('save')->assertForbidden();

        $this->assertNull(Permission::where('name', 'sneaky.add')->first());
        $this->assertModelExists($custom);
    }

    public function test_the_administrator_can_view_but_not_manage_privileges(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->get(route('admin.permissions.index'))->assertOk()->assertDontSee('wire:click="create"', false);
        Livewire::actingAs($admin)->test(Index::class)->call('create')->assertForbidden();
    }
}
