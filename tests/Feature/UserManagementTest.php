<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Livewire\Admin\Users\Create;
use App\Livewire\Admin\Users\Edit;
use App\Livewire\Admin\Users\Index;
use App\Livewire\Admin\Users\ResetPassword;
use App\Livewire\Admin\Users\Show;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function admin(): User
    {
        return $this->userWithRole(RoleName::Administrator);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function newUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
            'roles' => [RoleName::User->value],
            'status' => 'active',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createUserAs(User $actor, array $data): Testable
    {
        $component = Livewire::actingAs($actor)->test(Create::class);

        foreach ($data as $key => $value) {
            $component->set($key, $value);
        }

        return $component->call('save');
    }

    // ---- Access ----

    public function test_the_pages_open_for_users_with_permission(): void
    {
        $admin = $this->admin();
        $other = $this->userWithRole(RoleName::User);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSeeLivewire(Index::class)->assertSee($other->email);
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $other))->assertOk()->assertSee($other->name);
        $this->actingAs($admin)->get(route('admin.users.edit', $other))->assertOk();
    }

    public function test_users_without_permission_get_403_on_every_route(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $other = $this->userWithRole(RoleName::User);

        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.show', $other))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.edit', $other))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.reset-password', $other))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_livewire_components_refuse_unauthorized_users_directly(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $other = $this->userWithRole(RoleName::User);

        Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Create::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Show::class, ['user' => $other])->assertForbidden();
        Livewire::actingAs($staff)->test(Edit::class, ['user' => $other])->assertForbidden();
        Livewire::actingAs($staff)->test(ResetPassword::class, ['user' => $other])->assertForbidden();
    }

    public function test_index_actions_reauthorize_each_call(): void
    {
        // Can view the list, but not change status or delete.
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([PermissionName::DashboardView->value, PermissionName::UsersView->value]);
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($viewer)->test(Index::class)->call('toggleStatus', $target->id)->assertForbidden();
        Livewire::actingAs($viewer)->test(Index::class)->call('delete', $target->id)->assertForbidden();

        $this->assertTrue($target->fresh()?->isActive());
        $this->assertModelExists($target);
    }

    public function test_a_permission_revoked_after_the_page_loaded_is_enforced_on_the_next_action(): void
    {
        $admin = $this->admin();
        $target = $this->userWithRole(RoleName::User);

        $component = Livewire::actingAs($admin)->test(Index::class);

        $admin->syncRoles([RoleName::User->value]);

        $component->call('delete', $target->id)->assertForbidden();
        $this->assertModelExists($target);
    }

    // ---- Create ----

    public function test_an_authorized_user_can_create_a_user(): void
    {
        $this->createUserAs($this->admin(), $this->newUserData())
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'maria@example.com')->sole();
        $this->assertSame([RoleName::User->value], $user->getRoleNames()->all());
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('a-good-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_create_validates_unique_email_and_password_rules(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->createUserAs($this->admin(), $this->newUserData([
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
            'status' => 'banned',
            'roles' => ['Not a role'],
        ]))->assertHasErrors(['email' => 'unique', 'password', 'status', 'roles.0']);

        $this->createUserAs($this->admin(), $this->newUserData(['name' => '', 'email' => 'not-an-email']))
            ->assertHasErrors(['name' => 'required', 'email' => 'email']);
    }

    public function test_the_create_form_lists_only_assignable_roles(): void
    {
        Livewire::actingAs($this->admin())->test(Create::class)
            ->assertViewHas('roleOptions', fn (array $options): bool => array_column($options, 'name') === [RoleName::Administrator->value, RoleName::User->value]);
    }

    public function test_a_non_super_admin_cannot_assign_super_admin_even_with_a_crafted_payload(): void
    {
        $this->createUserAs($this->admin(), $this->newUserData(['roles' => [RoleName::SuperAdmin->value]]))
            ->assertHasErrors('roles');

        $this->createUserAs($this->admin(), $this->newUserData(['email' => 'owner@example.com', 'roles' => [RoleName::Owner->value]]))
            ->assertHasErrors('roles');

        $this->assertSame(0, User::whereIn('email', ['maria@example.com', 'owner@example.com'])->count());
    }

    public function test_a_super_admin_can_create_another_super_admin(): void
    {
        $this->createUserAs($this->superAdmin(), $this->newUserData(['roles' => [RoleName::SuperAdmin->value]]))
            ->assertHasNoErrors();

        $this->assertTrue(User::where('email', 'maria@example.com')->sole()->isSuperAdmin());
    }

    // ---- Update ----

    public function test_an_authorized_user_can_update_a_user_and_their_roles(): void
    {
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->set('roles', [RoleName::Administrator->value])
            ->set('status', 'inactive')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertSame('New Name', $target->name);
        $this->assertSame('new@example.com', $target->email);
        $this->assertSame([RoleName::Administrator->value], $target->getRoleNames()->all());
        $this->assertSame(UserStatus::Inactive, $target->status);
    }

    public function test_update_keeps_the_email_unique_but_allows_the_same_email(): void
    {
        $target = $this->userWithRole(RoleName::User);
        $other = User::factory()->create();

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('email', $other->email)
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('name', 'Same Email')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_a_non_super_admin_cannot_escalate_through_edit(): void
    {
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('roles', [RoleName::User->value, RoleName::SuperAdmin->value])
            ->call('save')
            ->assertHasErrors('roles');

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('roles', [RoleName::Owner->value])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertSame([RoleName::User->value], $target->fresh()?->getRoleNames()->all());
    }

    public function test_a_non_super_admin_cannot_remove_a_role_they_could_not_assign(): void
    {
        $owner = $this->userWithRole(RoleName::Owner);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $owner])
            ->set('roles', [RoleName::User->value])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertSame([RoleName::Owner->value], $owner->fresh()?->getRoleNames()->all());
    }

    public function test_a_non_super_admin_cannot_edit_a_super_admin(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($this->admin())->get(route('admin.users.edit', $superAdmin))->assertForbidden();
        Livewire::actingAs($this->userWithRole(RoleName::Owner))->test(Edit::class, ['user' => $superAdmin])->assertForbidden();
    }

    public function test_the_locked_user_id_cannot_be_swapped(): void
    {
        $target = $this->userWithRole(RoleName::User);
        $other = $this->userWithRole(RoleName::User);

        $this->expectException(\Throwable::class);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])->set('user', $other);
    }

    public function test_a_user_cannot_change_their_own_roles_or_status(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Edit::class, ['user' => $admin])
            ->set('roles', [RoleName::Administrator->value, RoleName::User->value])
            ->call('save')
            ->assertDispatched('toast', type: 'danger', message: 'You cannot change your own roles or status.');

        Livewire::actingAs($admin)->test(Edit::class, ['user' => $admin])
            ->set('status', 'inactive')
            ->call('save')
            ->assertDispatched('toast', type: 'danger');

        $admin->refresh();
        $this->assertSame([RoleName::Administrator->value], $admin->getRoleNames()->all());
        $this->assertTrue($admin->isActive());

        // Their own name and email can still change.
        Livewire::actingAs($admin)->test(Edit::class, ['user' => $admin])
            ->set('name', 'Renamed Admin')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();
        $this->assertSame('Renamed Admin', $admin->fresh()?->name);
    }

    public function test_the_last_super_admin_cannot_be_demoted_or_deactivated(): void
    {
        $superAdmin = $this->superAdmin();
        $otherSuperAdmin = $this->superAdmin();

        // With two, one can be demoted by the other.
        Livewire::actingAs($superAdmin)->test(Edit::class, ['user' => $otherSuperAdmin])
            ->set('roles', [RoleName::Owner->value])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();
        $this->assertFalse($otherSuperAdmin->fresh()?->isSuperAdmin());

        // Now the remaining one is protected, even from another Super Admin's attempt through the service.
        $this->assertTrue(app(RoleService::class)->isLastActiveSuperAdmin($superAdmin));

        $newSuperAdmin = $this->superAdmin();
        $newSuperAdmin->forceFill(['status' => UserStatus::Inactive])->save();

        Livewire::actingAs($newSuperAdmin)->test(Index::class)
            ->call('toggleStatus', $superAdmin->id);
        $this->assertTrue($superAdmin->fresh()?->isActive());
    }

    // ---- Status ----

    public function test_users_can_be_deactivated_and_activated(): void
    {
        $admin = $this->admin();
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($admin)->test(Index::class)->call('toggleStatus', $target->id)->assertDispatched('toast', type: 'success');
        $this->assertSame(UserStatus::Inactive, $target->fresh()?->status);

        Livewire::actingAs($admin)->test(Index::class)->call('toggleStatus', $target->id);
        $this->assertSame(UserStatus::Active, $target->fresh()?->status);
    }

    public function test_a_user_cannot_deactivate_themselves(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Index::class)->call('toggleStatus', $admin->id)->assertForbidden();

        $superAdmin = $this->superAdmin();
        $this->superAdmin();
        Livewire::actingAs($superAdmin)->test(Index::class)->call('toggleStatus', $superAdmin->id)->assertDispatched('toast', type: 'danger');

        $this->assertTrue($admin->fresh()?->isActive());
        $this->assertTrue($superAdmin->fresh()?->isActive());
    }

    public function test_a_deactivated_user_is_signed_out_on_the_next_request(): void
    {
        $target = $this->userWithRole(RoleName::User);
        Livewire::actingAs($this->admin())->test(Index::class)->call('toggleStatus', $target->id);

        $this->actingAs($target->fresh() ?? $target)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---- Delete ----

    public function test_an_authorized_user_can_delete_a_user(): void
    {
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->admin())->test(Index::class)->call('delete', $target->id)->assertDispatched('toast', type: 'success');

        $this->assertModelMissing($target);
    }

    public function test_a_user_cannot_delete_themselves(): void
    {
        $admin = $this->admin();
        Livewire::actingAs($admin)->test(Index::class)->call('delete', $admin->id)->assertForbidden();
        $this->assertModelExists($admin);

        $superAdmin = $this->superAdmin();
        $this->superAdmin();
        Livewire::actingAs($superAdmin)->test(Index::class)->call('delete', $superAdmin->id)
            ->assertDispatched('toast', type: 'danger', message: 'You cannot delete your own account.');
        $this->assertModelExists($superAdmin);
    }

    public function test_only_a_super_admin_can_delete_a_super_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $otherSuperAdmin = $this->superAdmin();

        Livewire::actingAs($this->userWithRole(RoleName::Owner))->test(Index::class)->call('delete', $otherSuperAdmin->id)->assertForbidden();
        $this->assertModelExists($otherSuperAdmin);

        Livewire::actingAs($superAdmin)->test(Index::class)->call('delete', $otherSuperAdmin->id)->assertDispatched('toast', type: 'success');
        $this->assertModelMissing($otherSuperAdmin);
    }

    // ---- Reset password ----

    public function test_reset_password_requires_a_recent_password_confirmation(): void
    {
        $target = $this->userWithRole(RoleName::User);

        $this->actingAs($this->admin())->get(route('admin.users.reset-password', $target))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_an_admin_can_set_a_new_password(): void
    {
        $target = $this->userWithRole(RoleName::User);
        $token = $target->getRememberToken();

        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->actingAs($this->admin())->get(route('admin.users.reset-password', $target))->assertOk();

        Livewire::actingAs($this->admin())->test(ResetPassword::class, ['user' => $target])
            ->set('password', 'brand-new-pass')
            ->set('password_confirmation', 'brand-new-pass')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertTrue(Hash::check('brand-new-pass', $target->password));
        $this->assertNotSame($token, $target->getRememberToken());
    }

    public function test_the_reset_action_redirects_to_confirm_when_the_confirmation_expired(): void
    {
        $target = $this->userWithRole(RoleName::User);
        $original = $target->password;

        Livewire::actingAs($this->admin())->test(ResetPassword::class, ['user' => $target])
            ->set('password', 'brand-new-pass')
            ->set('password_confirmation', 'brand-new-pass')
            ->call('save')
            ->assertRedirect(route('password.confirm'));

        $this->assertSame($original, $target->fresh()?->password);
    }

    public function test_an_admin_can_email_a_reset_link(): void
    {
        Notification::fake();
        $target = $this->userWithRole(RoleName::User);
        $this->withSession(['auth.password_confirmed_at' => time()]);
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($this->admin())->test(ResetPassword::class, ['user' => $target])
            ->call('sendLink')
            ->assertDispatched('toast', type: 'success');

        Notification::assertSentTo($target, ResetPasswordNotification::class);
    }

    public function test_reset_password_validates_the_new_password(): void
    {
        $target = $this->userWithRole(RoleName::User);
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($this->admin())->test(ResetPassword::class, ['user' => $target])
            ->set('password', 'short')
            ->set('password_confirmation', 'other')
            ->call('save')
            ->assertHasErrors('password');
    }

    // ---- List ----

    public function test_the_list_searches_filters_and_sorts(): void
    {
        $admin = $this->admin();
        User::factory()->withRole(RoleName::Administrator)->create(['name' => 'Zed Admin', 'email' => 'zed@example.com']);
        User::factory()->inactive()->withRole(RoleName::User)->create(['name' => 'Amy Inactive', 'email' => 'amy@example.com']);

        Livewire::actingAs($admin)->test(Index::class)
            ->set('search', 'zed')
            ->assertSee('Zed Admin')
            ->assertDontSee('Amy Inactive');

        Livewire::actingAs($admin)->test(Index::class)
            ->set('status', 'inactive')
            ->assertSee('Amy Inactive')
            ->assertDontSee('Zed Admin');

        Livewire::actingAs($admin)->test(Index::class)
            ->set('role', RoleName::Administrator->value)
            ->assertSee('Zed Admin')
            ->assertDontSee('Amy Inactive');

        Livewire::actingAs($admin)->test(Index::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['Amy Inactive', 'Zed Admin']);
    }

    public function test_unknown_sort_columns_and_filters_are_ignored(): void
    {
        Livewire::actingAs($this->admin())->test(Index::class)
            ->call('sortBy', 'password')
            ->assertSet('sort', 'created_at')
            ->set('status', 'nonsense')
            ->assertOk();

        Livewire::withQueryParams(['sort' => 'password', 'direction' => 'sideways'])
            ->actingAs($this->admin())
            ->test(Index::class)
            ->assertOk();
    }

    public function test_the_list_shows_an_empty_state(): void
    {
        Livewire::actingAs($this->admin())->test(Index::class)
            ->set('search', 'nobody-matches-this')
            ->assertSee('No users found');
    }

    public function test_filters_are_kept_in_the_query_string(): void
    {
        Livewire::withQueryParams(['search' => 'maria', 'status' => 'active'])
            ->actingAs($this->admin())
            ->test(Index::class)
            ->assertSet('search', 'maria')
            ->assertSet('status', 'active');
    }

    // ---- No password exposure ----

    public function test_passwords_never_appear_in_responses(): void
    {
        $target = User::factory()->withRole(RoleName::User)->create(['password' => 'super-secret-pass']);
        $admin = $this->admin();

        foreach ([route('admin.users.index'), route('admin.users.show', $target), route('admin.users.edit', $target)] as $url) {
            $content = $this->actingAs($admin)->get($url)->assertOk()->getContent() ?: '';
            $this->assertStringNotContainsString($target->password, $content);
            $this->assertStringNotContainsString('super-secret-pass', $content);
        }

        $this->createUserAs($admin, $this->newUserData())->assertSet('password', '')->assertSet('password_confirmation', '');
        $this->assertArrayNotHasKey('password', User::where('email', 'maria@example.com')->sole()->toArray());
    }
}
