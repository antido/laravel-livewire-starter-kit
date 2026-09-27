<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditEvent;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Admin\AuditTrails\Index;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Edit as RoleEdit;
use App\Livewire\Admin\Users\Create;
use App\Livewire\Admin\Users\Edit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\ResetPassword;
use App\Livewire\Auth\Login;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use LogicException;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function admin(): User
    {
        return $this->userWithRole(RoleName::Administrator);
    }

    private function latest(AuditEvent $event): AuditLog
    {
        return AuditLog::where('event', $event->value)->latest('id')->firstOrFail();
    }

    /**
     * Every stored value, flattened, for "never contains" checks.
     */
    private function allStoredValues(): string
    {
        return AuditLog::all()->map(fn (AuditLog $log): string => json_encode([$log->old_values, $log->new_values, $log->description]) ?: '')->implode("\n");
    }

    // ---- Writing entries ----

    public function test_creating_updating_and_deleting_a_user_writes_entries(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Create::class)
            ->set('name', 'Maria Santos')->set('email', 'maria@example.com')
            ->set('password', 'a-good-password')->set('password_confirmation', 'a-good-password')
            ->set('roles', [RoleName::User->value])->set('status', 'active')
            ->call('save');

        $maria = User::where('email', 'maria@example.com')->sole();
        $created = AuditLog::where('event', 'created')->where('auditable_id', $maria->id)->sole();
        $this->assertSame($admin->id, $created->user_id);
        $this->assertSame($admin->email, $created->user_email);
        $this->assertSame('Maria Santos', $created->new_values['name'] ?? null);
        $this->assertSame('Created user Maria Santos', $created->description);

        Livewire::actingAs($admin)->test(Edit::class, ['user' => $maria])
            ->set('name', 'Maria Reyes')
            ->call('save');

        $updated = $this->latest(AuditEvent::Updated);
        $this->assertSame(['name' => 'Maria Santos'], $updated->old_values);
        $this->assertSame(['name' => 'Maria Reyes'], $updated->new_values);

        Livewire::actingAs($admin)->test(UsersIndex::class)->call('delete', $maria->id);

        $deleted = $this->latest(AuditEvent::Deleted);
        $this->assertSame($maria->id, $deleted->auditable_id);
        $this->assertSame('maria@example.com', $deleted->old_values['email'] ?? null);
    }

    public function test_updates_store_only_the_changed_fields(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'same@example.com']);

        $user->update(['name' => 'New Name']);

        $entry = $this->latest(AuditEvent::Updated);
        $this->assertSame(['name'], array_keys($entry->old_values ?? []));
        $this->assertSame(['name'], array_keys($entry->new_values ?? []));
    }

    public function test_passwords_and_tokens_never_appear_in_the_values(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Create::class)
            ->set('name', 'Maria Santos')->set('email', 'maria@example.com')
            ->set('password', 'secret-pass-123')->set('password_confirmation', 'secret-pass-123')
            ->set('status', 'active')
            ->call('save');

        $maria = User::where('email', 'maria@example.com')->sole();
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($admin)->test(ResetPassword::class, ['user' => $maria])
            ->set('password', 'another-pass-456')->set('password_confirmation', 'another-pass-456')
            ->call('save');

        $stored = $this->allStoredValues();
        $this->assertStringNotContainsString('secret-pass-123', $stored);
        $this->assertStringNotContainsString('another-pass-456', $stored);
        $this->assertStringNotContainsString((string) $maria->fresh()?->password, $stored);
        $this->assertStringNotContainsString('remember_token', $stored);
        $this->assertStringNotContainsString('"password"', $stored);

        $reset = $this->latest(AuditEvent::PasswordReset);
        $this->assertSame($maria->id, $reset->auditable_id);
        $this->assertNull($reset->old_values);
        $this->assertNull($reset->new_values);
    }

    public function test_the_redaction_list_is_applied_at_every_depth(): void
    {
        $clean = app(AuditLogService::class)->redact([
            'name' => 'kept',
            'Password' => 'x',
            'api_token' => 'x',
            'client_secret' => 'x',
            'nested' => ['current_password' => 'x', 'token' => 'x', 'ok' => 1],
        ]);

        $this->assertSame(['name' => 'kept', 'nested' => ['ok' => 1]], $clean);
    }

    public function test_role_syncs_are_logged_with_old_and_new_values(): void
    {
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->admin())->test(Edit::class, ['user' => $target])
            ->set('roles', [RoleName::Administrator->value])
            ->call('save');

        $entry = $this->latest(AuditEvent::RolesSynced);
        $this->assertSame(['roles' => [RoleName::User->value]], $entry->old_values);
        $this->assertSame(['roles' => [RoleName::Administrator->value]], $entry->new_values);
        $this->assertSame("Changed the roles of {$target->name}", $entry->description);
    }

    public function test_permission_syncs_are_logged_with_old_and_new_values(): void
    {
        $role = Role::findOrCreate('Night shift', 'web');
        $role->syncPermissions(['dashboard.view']);

        Livewire::actingAs($this->userWithRole(RoleName::Owner))->test(RoleEdit::class, ['role' => $role])
            ->set('permissions', ['dashboard.view', 'users.view'])
            ->call('save');

        $entry = $this->latest(AuditEvent::PermissionsSynced);
        $this->assertSame(Role::class, $entry->auditable_type);
        $this->assertSame(['permissions' => ['dashboard.view']], $entry->old_values);
        $this->assertSame(['permissions' => ['dashboard.view', 'users.view']], $entry->new_values);
    }

    public function test_status_changes_are_logged_once(): void
    {
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->admin())->test(UsersIndex::class)->call('toggleStatus', $target->id);

        $entry = $this->latest(AuditEvent::StatusChanged);
        $this->assertSame(['status' => 'active'], $entry->old_values);
        $this->assertSame(['status' => 'inactive'], $entry->new_values);
        $this->assertSame(0, AuditLog::where('event', 'updated')->where('auditable_id', $target->id)->count());
    }

    public function test_privilege_changes_are_logged(): void
    {
        $owner = $this->userWithRole(RoleName::Owner);

        Livewire::actingAs($owner)->test(PermissionsIndex::class)->call('create')->set('name', 'reports.run')->call('save');

        $this->assertSame('Created privilege reports.run', $this->latest(AuditEvent::Created)->description);
    }

    public function test_logins_and_logouts_are_logged(): void
    {
        $user = User::factory()->withRole(RoleName::User)->create(['password' => 'secret-pass-1']);

        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'secret-pass-1')->call('login');

        $login = $this->latest(AuditEvent::Login);
        $this->assertSame($user->id, $login->user_id);
        $this->assertNotNull($login->ip_address);

        $this->post(route('logout'));
        $this->assertSame($user->id, $this->latest(AuditEvent::Logout)->user_id);
    }

    public function test_a_failed_login_is_logged_without_the_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        Livewire::test(Login::class)->set('email', strtoupper($user->email))->set('password', 'wrong-guess-99')->call('login');

        $entry = $this->latest(AuditEvent::LoginFailed);
        $this->assertNull($entry->user_id);
        $this->assertSame(['email' => strtolower($user->email)], $entry->new_values);
        $this->assertStringNotContainsString('wrong-guess-99', $this->allStoredValues());
    }

    public function test_audit_entries_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $entry = app(AuditLogService::class)->record(AuditEvent::Login, null, 'Something happened');

        try {
            $entry->update(['description' => 'Tampered']);
            $this->fail('An audit entry was updated.');
        } catch (LogicException) {
            // Expected.
        }

        try {
            $entry->delete();
            $this->fail('An audit entry was deleted.');
        } catch (LogicException) {
            // Expected.
        }

        $this->assertSame('Something happened', $entry->fresh()?->description);
    }

    public function test_the_actor_name_survives_the_deletion_of_their_account(): void
    {
        $actor = $this->userWithRole(RoleName::User);
        $entry = app(AuditLogService::class)->record(AuditEvent::Login, null, 'Did something', actor: $actor);

        $actor->delete();

        $entry = $entry->fresh()?->load('user');
        $this->assertNull($entry?->user_id);
        $this->assertSame($actor->name.' (deleted)', $entry?->actorName());
    }

    public function test_retention_prunes_only_old_entries_when_configured(): void
    {
        $old = app(AuditLogService::class)->record(AuditEvent::Login, null, 'Old');
        $old->newQuery()->whereKey($old->id)->toBase()->update(['created_at' => now()->subDays(100)]);
        app(AuditLogService::class)->record(AuditEvent::Login, null, 'Recent');

        Artisan::call('model:prune', ['--model' => [AuditLog::class]]);
        $this->assertSame(2, AuditLog::count(), 'Nothing is pruned without a retention period.');

        config(['audit.retention_days' => 30]);
        Artisan::call('model:prune', ['--model' => [AuditLog::class]]);
        $this->assertSame(['Recent'], AuditLog::pluck('description')->all());
    }

    // ---- Viewer ----

    public function test_a_user_without_audit_trails_view_gets_403(): void
    {
        $staff = $this->userWithRole(RoleName::User);

        $this->actingAs($staff)->get(route('admin.audit-trails.index'))->assertForbidden();
        Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
    }

    public function test_permitted_users_see_the_trail(): void
    {
        $admin = $this->admin();
        app(AuditLogService::class)->record(AuditEvent::Login, $admin, 'Visible entry', actor: $admin);

        $this->actingAs($admin)->get(route('admin.audit-trails.index'))
            ->assertOk()
            ->assertSee('Visible entry')
            ->assertSee('Logged in');
    }

    public function test_filters_return_the_correct_rows(): void
    {
        $admin = $this->admin();
        $other = $this->userWithRole(RoleName::User);
        $audit = app(AuditLogService::class);

        $a = $audit->record(AuditEvent::Login, $admin, 'Alpha entry', actor: $admin);
        $b = $audit->record(AuditEvent::RolesSynced, $other, 'Bravo entry', actor: $other);
        $c = $audit->record(AuditEvent::LoginFailed, null, 'Charlie entry');
        $a->newQuery()->whereKey($a->id)->toBase()->update(['created_at' => now()->subDays(10)]);

        $ids = fn ($component): array => $component->viewData('entries')->pluck('id')->all();

        $this->assertSame([$b->id], $ids(Livewire::actingAs($admin)->test(Index::class)->set('search', 'Bravo')));
        $this->assertSame([$b->id], $ids(Livewire::actingAs($admin)->test(Index::class)->set('user', (string) $other->id)->set('search', 'entry')));
        $this->assertSame([$c->id], $ids(Livewire::actingAs($admin)->test(Index::class)->set('user', 'system')->set('search', 'entry')));
        $this->assertSame([$b->id], $ids(Livewire::actingAs($admin)->test(Index::class)->set('event', 'roles_synced')));
        $this->assertContains($b->id, $ids(Livewire::actingAs($admin)->test(Index::class)->set('subject', User::class)));
        $this->assertNotContains($c->id, $ids(Livewire::actingAs($admin)->test(Index::class)->set('subject', User::class)));

        $recent = $ids(Livewire::actingAs($admin)->test(Index::class)->set('from', now()->subDay()->format('Y-m-d'))->set('search', 'entry'));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $recent);

        $older = $ids(Livewire::actingAs($admin)->test(Index::class)->set('to', now()->subDays(5)->format('Y-m-d'))->set('search', 'entry'));
        $this->assertSame([$a->id], $older);

        // Invalid filter values are ignored rather than breaking the page.
        Livewire::actingAs($admin)->test(Index::class)->set('from', 'not-a-date')->set('event', 'nonsense')->set('user', 'drop table')->assertOk();
    }

    public function test_entries_are_newest_first_and_paginated_by_25(): void
    {
        $admin = $this->admin();
        foreach (range(1, 30) as $i) {
            app(AuditLogService::class)->record(AuditEvent::Login, null, "Entry {$i}");
        }

        $entries = Livewire::actingAs($admin)->test(Index::class)->set('search', 'Entry')->viewData('entries');

        $this->assertSame(25, $entries->count());
        $this->assertSame('Entry 30', $entries->first()->description);
    }

    public function test_the_detail_view_shows_a_before_and_after_table(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Before Name']);
        $user->update(['name' => '<b>After</b> Name']);
        $entry = $this->latest(AuditEvent::Updated);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('show', $entry->id)
            ->assertSet('showDetail', true)
            ->assertSee('Before Name')
            ->assertSee('&lt;b&gt;After&lt;/b&gt; Name', false)
            ->assertDontSee('<b>After</b>', false)
            ->assertSee('data-changed="true"', false);
    }

    public function test_the_viewer_offers_no_way_to_change_entries(): void
    {
        $component = new Index;

        foreach (['delete', 'update', 'destroy', 'edit', 'save'] as $method) {
            $this->assertFalse(method_exists($component, $method), "The viewer has a {$method} action.");
        }

        $this->assertFalse(PermissionName::tryFrom('audit_trails.delete') !== null);
    }

    public function test_the_audit_trails_menu_item_appears_for_permitted_users(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertSee('Audit Trails');
        $this->actingAs($this->userWithRole(RoleName::User))->get(route('dashboard'))->assertDontSee('Audit Trails');
    }
}
