<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditEvent;
use App\Enums\EmployeeStatus;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\ActionNotAllowedException;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Employees\Create;
use App\Livewire\Employees\Edit;
use App\Livewire\Employees\Index;
use App\Livewire\Employees\Show;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private function admin(): User
    {
        return $this->userWithRole(RoleName::Administrator);
    }

    /**
     * A user who manages employees but cannot touch user accounts.
     */
    private function employeeManager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            PermissionName::DashboardView->value,
            PermissionName::EmployeesView->value,
            PermissionName::EmployeesCreate->value,
            PermissionName::EmployeesUpdate->value,
            PermissionName::EmployeesDelete->value,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function fillForm(Testable $component, array $fields): Testable
    {
        foreach (array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'position' => 'Cashier',
            'status' => 'active',
        ], $fields) as $key => $value) {
            $component->set("form.{$key}", $value);
        }

        return $component;
    }

    // ---- Access ----

    public function test_the_pages_open_with_permission(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->get(route('employees.index'))->assertOk()->assertSee($employee->full_name);
        $this->actingAs($admin)->get(route('employees.create'))->assertOk();
        $this->actingAs($admin)->get(route('employees.show', $employee))->assertOk()->assertSee($employee->employee_number);
        $this->actingAs($admin)->get(route('employees.edit', $employee))->assertOk();
    }

    public function test_unauthorized_users_get_403_on_routes_and_livewire_actions(): void
    {
        $staff = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->create();

        $this->actingAs($staff)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('employees.show', $employee))->assertForbidden();
        $this->actingAs($staff)->get(route('employees.edit', $employee))->assertForbidden();

        Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Create::class)->assertForbidden();
        Livewire::actingAs($staff)->test(Edit::class, ['employee' => $employee])->assertForbidden();
        Livewire::actingAs($staff)->test(Show::class, ['employee' => $employee])->assertForbidden();

        // View-only users cannot archive or restore.
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([PermissionName::DashboardView->value, PermissionName::EmployeesView->value]);
        Livewire::actingAs($viewer)->test(Index::class)->call('archive', $employee->id)->assertForbidden();
        $this->assertNotSoftDeleted($employee);

        $employee->delete();
        Livewire::actingAs($viewer)->test(Index::class)->call('restore', $employee->id)->assertForbidden();
        $this->assertSoftDeleted($employee);
    }

    public function test_the_sidebar_shows_employees_only_to_permitted_users(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertSee('href="'.route('employees.index').'"', false);
        $this->actingAs($this->userWithRole(RoleName::User))->get(route('dashboard'))->assertDontSee('href="'.route('employees.index').'"', false);
    }

    // ---- Create, update, archive, restore ----

    public function test_an_employee_can_be_created_without_an_account(): void
    {
        $this->fillForm(Livewire::actingAs($this->employeeManager())->test(Create::class), [
            'middle_name' => 'Santos',
            'suffix' => 'Jr.',
            'hire_date' => '2024-01-15',
            'phone' => '0917 123 4567',
        ])->call('save')->assertHasNoErrors()->assertRedirect();

        $employee = Employee::sole();
        $this->assertSame('Juan Santos Dela Cruz Jr.', $employee->full_name);
        $this->assertMatchesRegularExpression('/^EMP-\d{5}$/', $employee->employee_number);
        $this->assertNull($employee->user_id);
        $this->assertSame('2024-01-15', $employee->hire_date?->format('Y-m-d'));
        $this->assertNotNull($employee->created_by);
    }

    public function test_employee_fields_are_validated(): void
    {
        $this->fillForm(Livewire::actingAs($this->admin())->test(Create::class), [
            'first_name' => '',
            'position' => '',
            'status' => 'fired',
            'email' => 'not-an-email',
            'phone' => 'call me <maybe>',
            'hire_date' => now()->addDay()->format('Y-m-d'),
        ])->call('save')->assertHasErrors([
            'form.first_name' => 'required',
            'form.position' => 'required',
            'form.status',
            'form.email' => 'email',
            'form.phone' => 'regex',
            'form.hire_date' => 'before_or_equal',
        ]);

        $this->assertSame(0, Employee::count());
    }

    public function test_an_employee_can_be_updated(): void
    {
        $employee = Employee::factory()->create(['position' => 'Clerk']);

        Livewire::actingAs($this->employeeManager())->test(Edit::class, ['employee' => $employee])
            ->set('form.position', 'Supervisor')
            ->set('form.status', 'on_leave')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Supervisor', $employee->position);
        $this->assertSame(EmployeeStatus::OnLeave, $employee->status);
    }

    public function test_employees_can_be_archived_and_restored(): void
    {
        $manager = $this->employeeManager();
        $employee = Employee::factory()->create();

        Livewire::actingAs($manager)->test(Index::class)->call('archive', $employee->id)->assertDispatched('toast', type: 'success');
        $this->assertSoftDeleted($employee);

        Livewire::actingAs($manager)->test(Index::class)
            ->assertDontSee($employee->full_name)
            ->set('archived', true)
            ->assertSee($employee->full_name);

        // An archived employee cannot be edited until restored, but can still be viewed.
        $this->actingAs($manager)->get(route('employees.edit', $employee))->assertNotFound();
        $this->actingAs($manager)->get(route('employees.show', $employee))->assertOk()->assertSee('archived');

        Livewire::actingAs($manager)->test(Index::class)->call('restore', $employee->id)->assertDispatched('toast', type: 'success');
        $this->assertNotSoftDeleted($employee);
    }

    public function test_the_list_filters_by_search_status_and_account(): void
    {
        $linked = Employee::factory()->withAccount()->create(['first_name' => 'Ana', 'last_name' => 'Reyes', 'position' => 'Cashier']);
        $unlinked = Employee::factory()->resigned()->create(['first_name' => 'Ben', 'last_name' => 'Santos', 'position' => 'Driver']);
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Index::class)->set('search', 'Driver')->assertSee('Ben Santos')->assertDontSee('Ana Reyes');
        Livewire::actingAs($admin)->test(Index::class)->set('search', $linked->employee_number)->assertSee('Ana Reyes')->assertDontSee('Ben Santos');
        Livewire::actingAs($admin)->test(Index::class)->set('search', 'Ana Reyes')->assertSee('Ana Reyes');
        Livewire::actingAs($admin)->test(Index::class)->set('status', 'resigned')->assertSee('Ben Santos')->assertDontSee('Ana Reyes');
        Livewire::actingAs($admin)->test(Index::class)->set('account', 'yes')->assertSee('Ana Reyes')->assertDontSee('Ben Santos');
        Livewire::actingAs($admin)->test(Index::class)->set('account', 'no')->assertSee('Ben Santos')->assertDontSee('Ana Reyes');
        $this->assertNotNull($unlinked->id);
    }

    // ---- Accounts ----

    public function test_creating_an_employee_with_a_new_account_creates_and_links_both(): void
    {
        $admin = $this->admin();

        $this->fillForm(Livewire::actingAs($admin)->test(Create::class), [
            'account_mode' => 'create',
            'account_email' => 'Juan@Example.com',
            'account_password' => 'a-good-password',
            'account_password_confirmation' => 'a-good-password',
            'account_roles' => [RoleName::User->value],
        ])->call('save')->assertHasNoErrors()->assertRedirect()->assertSet('form.account_password', null);

        $employee = Employee::with('user')->sole();
        $user = $employee->user;
        $this->assertNotNull($user);
        $this->assertSame('juan@example.com', $user->email);
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame([RoleName::User->value], $user->getRoleNames()->all());
        $this->assertTrue(Hash::check('a-good-password', $user->password));
        $this->assertSame(1, AuditLog::where('event', AuditEvent::EmployeeAccountLinked->value)->count());
    }

    public function test_creating_an_account_without_users_create_is_rejected_even_with_a_crafted_payload(): void
    {
        $this->fillForm(Livewire::actingAs($this->employeeManager())->test(Create::class), [
            'account_mode' => 'create',
            'account_email' => 'sneaky@example.com',
            'account_password' => 'a-good-password',
            'account_password_confirmation' => 'a-good-password',
        ])->call('save');

        $this->assertNull(User::where('email', 'sneaky@example.com')->first());

        // Straight through the service as well.
        $this->expectException(ActionNotAllowedException::class);
        app(EmployeeService::class)->create($this->employeeManager(), [
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'position' => 'Clerk', 'status' => 'active',
            'account_mode' => 'create', 'account_email' => 'sneaky2@example.com', 'account_password' => 'a-good-password',
        ]);
    }

    public function test_new_account_roles_follow_can_assign(): void
    {
        $this->fillForm(Livewire::actingAs($this->admin())->test(Create::class), [
            'account_mode' => 'create',
            'account_email' => 'juan@example.com',
            'account_password' => 'a-good-password',
            'account_password_confirmation' => 'a-good-password',
            'account_roles' => [RoleName::SuperAdmin->value],
        ])->call('save')->assertHasErrors('roles');

        $this->assertSame(0, Employee::count(), 'Nothing is saved when the account cannot be created.');
        $this->assertNull(User::where('email', 'juan@example.com')->first());
    }

    public function test_an_existing_user_can_be_linked(): void
    {
        $user = $this->userWithRole(RoleName::User);

        $this->fillForm(Livewire::actingAs($this->admin())->test(Create::class), [
            'account_mode' => 'link',
            'link_user_id' => $user->id,
        ])->call('save')->assertHasNoErrors()->assertRedirect();

        $this->assertSame($user->id, Employee::sole()->user_id);
    }

    public function test_linking_fails_for_a_user_who_already_has_an_employee(): void
    {
        $user = $this->userWithRole(RoleName::User);
        Employee::factory()->withAccount($user)->create();

        $this->fillForm(Livewire::actingAs($this->admin())->test(Create::class), [
            'account_mode' => 'link',
            'link_user_id' => $user->id,
        ])->call('save')->assertHasErrors(['form.link_user_id' => 'unique']);

        $this->assertSame(1, Employee::count());

        // The database refuses it too.
        $this->expectException(QueryException::class);
        $other = Employee::factory()->create();
        $other->user_id = $user->id;
        $other->save();
    }

    public function test_linking_requires_users_update(): void
    {
        $user = $this->userWithRole(RoleName::User);

        $this->fillForm(Livewire::actingAs($this->employeeManager())->test(Create::class), [
            'account_mode' => 'link',
            'link_user_id' => $user->id,
        ])->call('save');

        $this->assertSame(0, Employee::whereNotNull('user_id')->count());
    }

    public function test_a_super_admin_account_cannot_be_linked_by_a_non_super_admin(): void
    {
        $superAdmin = $this->superAdmin();

        $this->fillForm(Livewire::actingAs($this->admin())->test(Create::class), [
            'account_mode' => 'link',
            'link_user_id' => $superAdmin->id,
        ])->call('save')->assertDispatched('toast', type: 'danger');

        $this->assertSame(0, Employee::count());
    }

    public function test_an_account_can_be_linked_on_edit_and_unlinked_without_deleting_the_user(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create();
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($admin)->test(Edit::class, ['employee' => $employee])
            ->set('form.account_mode', 'link')
            ->set('form.link_user_id', $user->id)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame($user->id, $employee->fresh()?->user_id);

        Livewire::actingAs($admin)->test(Edit::class, ['employee' => $employee->fresh()])
            ->call('unlink')
            ->assertDispatched('toast', type: 'success');

        $this->assertNull($employee->fresh()?->user_id);
        $this->assertModelExists($user);
        $this->assertSame(1, AuditLog::where('event', AuditEvent::EmployeeAccountUnlinked->value)->count());
    }

    public function test_deleting_the_user_keeps_the_employee_and_clears_the_link(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->withAccount($user)->create();

        Livewire::actingAs($this->admin())->test(UsersIndex::class)->call('delete', $user->id);

        $this->assertModelMissing($user);
        $this->assertModelExists($employee);
        $this->assertNull($employee->fresh()?->user_id);
    }

    public function test_the_deactivate_on_resign_option_deactivates_the_linked_user(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->withAccount($user)->create();

        Livewire::actingAs($this->admin())->test(Edit::class, ['employee' => $employee])
            ->set('form.status', 'resigned')
            ->assertSee('Also deactivate the linked user account')
            ->assertSet('form.deactivate_account', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(EmployeeStatus::Resigned, $employee->fresh()?->status);
        $this->assertSame(UserStatus::Inactive, $user->fresh()?->status);
    }

    public function test_the_linked_user_stays_active_when_the_option_is_cleared(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->withAccount($user)->create();

        Livewire::actingAs($this->admin())->test(Edit::class, ['employee' => $employee])
            ->set('form.status', 'terminated')
            ->set('form.deactivate_account', false)
            ->call('save');

        $this->assertSame(UserStatus::Active, $user->fresh()?->status);
    }

    // ---- Numbers ----

    public function test_employee_numbers_are_unique_sequential_and_ignore_client_input(): void
    {
        $admin = $this->admin();

        foreach (['Ana', 'Ben', 'Cara'] as $name) {
            $this->fillForm(Livewire::actingAs($admin)->test(Create::class), ['first_name' => $name])->call('save');
        }

        $numbers = Employee::orderBy('id')->pluck('employee_number')->all();
        $this->assertSame(['EMP-00001', 'EMP-00002', 'EMP-00003'], $numbers);

        // Crafted employee_number, user_id and created_by values are ignored: only validated detail fields are saved.
        $intruder = User::factory()->create();
        $component = $this->fillForm(Livewire::actingAs($admin)->test(Create::class), ['first_name' => 'Dan']);
        foreach (['employee_number' => 'EMP-99999', 'user_id' => $intruder->id, 'created_by' => $intruder->id] as $field => $value) {
            try {
                $component->set("form.{$field}", $value);
            } catch (\Throwable) {
                // Refusing the property outright is fine too.
            }
        }
        $component->call('save');

        $dan = Employee::where('first_name', 'Dan')->sole();
        $this->assertSame('EMP-00004', $dan->employee_number);
        $this->assertNull($dan->user_id);
        $this->assertSame($admin->id, $dan->created_by);
    }

    public function test_the_generator_skips_numbers_already_taken_including_archived(): void
    {
        $archived = Employee::factory()->create(['employee_number' => 'EMP-00007']);
        $archived->delete();

        $this->assertSame('EMP-00008', app(EmployeeService::class)->generateEmployeeNumber());
    }

    // ---- Audit and the show page ----

    public function test_employee_changes_are_audited(): void
    {
        $admin = $this->admin();
        $this->fillForm(Livewire::actingAs($admin)->test(Create::class), [])->call('save');
        $employee = Employee::sole();

        Livewire::actingAs($admin)->test(Edit::class, ['employee' => $employee])->set('form.position', 'Supervisor')->call('save');
        Livewire::actingAs($admin)->test(Index::class)->call('archive', $employee->id);
        Livewire::actingAs($admin)->test(Index::class)->call('restore', $employee->id);

        $events = AuditLog::where('auditable_type', Employee::class)->where('auditable_id', $employee->id)->orderBy('id')->pluck('event')->map->value->all();
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $events);

        $updated = AuditLog::where('event', 'updated')->where('auditable_id', $employee->id)->sole();
        $this->assertSame(['position' => 'Cashier'], $updated->old_values);
        $this->assertSame(['position' => 'Supervisor'], $updated->new_values);
    }

    public function test_the_show_page_lists_recent_activity_only_with_audit_permission(): void
    {
        $employee = Employee::factory()->create();
        $employee->update(['position' => 'Floor manager']);

        $this->actingAs($this->admin())->get(route('employees.show', $employee))->assertSee('Recent activity')->assertSee('Updated employee');
        $this->actingAs($this->employeeManager())->get(route('employees.show', $employee))->assertOk()->assertDontSee('Recent activity');
    }

    public function test_the_users_list_and_profile_show_the_linked_employee(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $employee = Employee::factory()->withAccount($user)->create(['first_name' => 'Linked', 'last_name' => 'Person']);
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(UsersIndex::class)->assertSee('Linked Person');
        $this->actingAs($admin)->get(route('admin.users.show', $user))
            ->assertSee('Linked Person')
            ->assertSee('href="'.route('employees.show', $employee).'"', false);
    }
}
