<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditEvent;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Exceptions\ActionNotAllowedException;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Create as RoleCreate;
use App\Livewire\Admin\Roles\Edit as RoleEdit;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Employees\Edit as EmployeeEdit;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

/**
 * Tests added in the coverage review (Task 17): validation failures, audit entries for every service
 * write, and authorization for the actions not covered elsewhere.
 */
class CoverageGapsTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    // ---- Validation failures of every form request ----

    public function test_the_auth_forms_reject_invalid_input(): void
    {
        Livewire::test(Login::class)->set('email', 'not-an-email')->set('password', '')->call('login')
            ->assertHasErrors(['email' => 'email', 'password' => 'required']);

        Livewire::test(ForgotPassword::class)->set('email', '')->call('sendResetLink')
            ->assertHasErrors(['email' => 'required']);

        Livewire::test(ResetPassword::class, ['token' => 'x'])->set('email', 'bad')->set('password', 'a')->call('resetPassword')
            ->assertHasErrors(['email' => 'email', 'password']);

        Livewire::actingAs(User::factory()->create())->test(ConfirmPassword::class)->set('password', '')->call('confirm')
            ->assertHasErrors(['password' => 'required']);

        Livewire::test(Register::class)->set('name', str_repeat('a', 256))->set('email', 'UPPER@EXAMPLE.COM')->call('register')
            ->assertHasErrors(['name' => 'max', 'email' => 'lowercase', 'password' => 'required']);
    }

    public function test_the_role_form_rejects_invalid_input(): void
    {
        $owner = $this->userWithRole(RoleName::Owner);

        Livewire::actingAs($owner)->test(RoleCreate::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
        Livewire::actingAs($owner)->test(RoleCreate::class)->set('name', str_repeat('a', 51))->call('save')->assertHasErrors(['name' => 'max']);
        Livewire::actingAs($owner)->test(RoleCreate::class)->set('name', 'Dup')->set('permissions', ['users.view', 'users.view'])->call('save')->assertHasErrors('permissions.0');
    }

    // ---- Audit entries for every service write ----

    public function test_role_create_rename_and_delete_are_audited(): void
    {
        $owner = $this->userWithRole(RoleName::Owner);

        Livewire::actingAs($owner)->test(RoleCreate::class)->set('name', 'Night shift')->set('permissions', ['dashboard.view'])->call('save');
        $role = Role::findByName('Night shift');
        $created = AuditLog::where('event', 'created')->where('auditable_type', Role::class)->sole();
        $this->assertSame(['name' => 'Night shift', 'permissions' => ['dashboard.view']], $created->new_values);

        Livewire::actingAs($owner)->test(RoleEdit::class, ['role' => $role])->set('name', 'Day shift')->call('save');
        $renamed = AuditLog::where('event', 'updated')->where('auditable_type', Role::class)->sole();
        $this->assertSame(['name' => 'Night shift'], $renamed->old_values);
        $this->assertSame(['name' => 'Day shift'], $renamed->new_values);

        Livewire::actingAs($owner)->test(RolesIndex::class)->call('delete', $role->id);
        $deleted = AuditLog::where('event', 'deleted')->where('auditable_type', Role::class)->sole();
        $this->assertSame('Day shift', $deleted->old_values['name'] ?? null);
    }

    public function test_privilege_rename_and_delete_are_audited(): void
    {
        $owner = $this->userWithRole(RoleName::Owner);
        $permission = Permission::findOrCreate('reports.run', 'web');

        Livewire::actingAs($owner)->test(PermissionsIndex::class)->call('edit', $permission->id)->set('name', 'reports.execute')->call('save');
        Livewire::actingAs($owner)->test(PermissionsIndex::class)->call('delete', $permission->id);

        $events = AuditLog::where('auditable_type', Permission::class)->orderBy('id')->get();
        $this->assertSame(['updated', 'deleted'], $events->map(fn (AuditLog $log): string => $log->event->value)->all());
        $this->assertSame(['name' => 'reports.run'], $events[0]->old_values);
        $this->assertSame(['name' => 'reports.execute'], $events[1]->old_values);
    }

    public function test_self_registration_is_audited_as_a_created_user(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'New Person')->set('email', 'new@example.com')
            ->set('password', 'password-123')->set('password_confirmation', 'password-123')
            ->call('register');

        $entry = AuditLog::where('event', AuditEvent::Created->value)->where('auditable_type', User::class)->sole();
        $this->assertNull($entry->user_id, 'Nobody was signed in yet, so the actor is the system.');
        $this->assertSame('new@example.com', $entry->new_values['email'] ?? null);
    }

    // ---- Authorization of the remaining actions ----

    public function test_notification_components_require_a_signed_in_user(): void
    {
        Livewire::test(Bell::class)->assertForbidden();
        Livewire::test(NotificationsIndex::class)->assertForbidden();
    }

    public function test_unlinking_needs_permission_to_update_the_linked_user(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo([PermissionName::DashboardView->value, PermissionName::EmployeesView->value, PermissionName::EmployeesUpdate->value]);
        $user = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->withAccount($user)->create();

        Livewire::actingAs($manager)->test(EmployeeEdit::class, ['employee' => $employee])
            ->call('unlink')
            ->assertDispatched('toast', type: 'danger');

        $this->assertSame($user->id, $employee->fresh()?->user_id);
    }

    public function test_the_service_refuses_to_demote_the_last_active_super_admin(): void
    {
        $superAdmin = $this->superAdmin();

        $this->expectException(ActionNotAllowedException::class);

        // Another Super Admin exists but is inactive, so $superAdmin is the last active one.
        $other = $this->superAdmin();
        $other->forceFill(['status' => 'inactive'])->save();

        app(UserService::class)->update($other, $superAdmin, [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'roles' => [RoleName::Owner->value],
            'status' => 'active',
        ]);
    }

    // ---- Notification URL safety ----

    public function test_only_internal_paths_are_accepted_as_notification_links(): void
    {
        $service = app(NotificationService::class);
        $make = fn (string $url): DatabaseNotification => new DatabaseNotification(['id' => (string) Str::uuid(), 'data' => ['url' => $url]]);

        $this->assertSame('/employees/3?tab=info', $service->safeUrl($make('/employees/3?tab=info')));

        foreach (['', 'https://evil.test', '//evil.test/path', '/\\evil.test', 'javascript:alert(1)', 'mailto:x@y.z', "/foo\nbar", 'relative/path'] as $bad) {
            $this->assertNull($service->safeUrl($make($bad)), "Accepted: {$bad}");
        }
    }
}
