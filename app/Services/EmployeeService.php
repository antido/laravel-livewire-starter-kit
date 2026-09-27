<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\EmployeeStatus;
use App\Enums\UserStatus;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeAccountLinkedNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Employee records and their optional login accounts. Anything user-related goes through UserService,
 * so the user rules (role escalation, Super Admin protection) apply here too.
 *
 * @phpstan-type EmployeeInput array{first_name: string, middle_name?: string|null, last_name: string, suffix?: string|null, email?: string|null, phone?: string|null, address?: string|null, position: string, hire_date?: string|null, status: string, notes?: string|null, account_mode?: string, account_email?: string|null, account_password?: string|null, account_roles?: list<string>, link_user_id?: int|string|null, deactivate_account?: bool}
 */
class EmployeeService
{
    /**
     * How many times to retry when two requests generate the same employee number at once.
     */
    private const int NUMBER_ATTEMPTS = 3;

    public function __construct(
        private readonly UserService $users,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Create an employee, optionally with a new login account or linked to an existing one.
     *
     * @param  EmployeeInput  $validated
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function create(User $actor, array $validated): Employee
    {
        $mode = $validated['account_mode'] ?? 'none';
        $this->ensureCanHandleAccount($actor, $mode, $validated);

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($actor, $validated, $mode): Employee {
                    $employee = new Employee($this->details($validated));
                    $employee->employee_number = $this->generateEmployeeNumber();
                    $employee->save();

                    $this->applyAccountMode($actor, $employee, $mode, $validated);

                    return $employee;
                });
            } catch (UniqueConstraintViolationException $exception) {
                // Another request took the same number between our read and our insert: try the next one.
                if ($attempt >= self::NUMBER_ATTEMPTS || ! str_contains($exception->getMessage(), 'employee_number')) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  EmployeeInput  $validated
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function update(User $actor, Employee $employee, array $validated): Employee
    {
        $mode = $validated['account_mode'] ?? 'keep';
        $this->ensureCanHandleAccount($actor, $mode, $validated);

        $previousStatus = $employee->status;
        $newStatus = EmployeeStatus::from($validated['status']);
        $deactivateAccount = ($validated['deactivate_account'] ?? false) === true
            && $newStatus->hasLeft()
            && $newStatus !== $previousStatus
            && $employee->user_id !== null;

        return DB::transaction(function () use ($actor, $employee, $validated, $mode, $deactivateAccount): Employee {
            $employee->fill($this->details($validated));
            $employee->save();

            if ($mode === 'create' || $mode === 'link') {
                $this->applyAccountMode($actor, $employee, $mode, $validated);
            }

            if ($deactivateAccount) {
                $this->deactivateLinkedAccount($actor, $employee);
            }

            return $employee;
        });
    }

    /**
     * Archive (soft delete). The linked account, if any, is kept.
     */
    public function archive(User $actor, Employee $employee): void
    {
        DB::transaction(fn () => $employee->delete());
    }

    public function restore(User $actor, Employee $employee): void
    {
        DB::transaction(fn () => $employee->restore());
    }

    /**
     * Create a login account for the employee. Requires users.create; the roles follow RoleService::canAssign().
     *
     * @param  array{account_email: string, account_password: string, account_roles?: list<string>}  $account
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function createAccount(User $actor, Employee $employee, array $account): User
    {
        if (! $actor->can('create', User::class)) {
            throw new ActionNotAllowedException(__('You are not allowed to create user accounts.'));
        }

        $this->ensureNotLinked($employee);

        return DB::transaction(function () use ($actor, $employee, $account): User {
            $user = $this->users->create($actor, [
                'name' => $employee->full_name,
                'email' => $account['account_email'],
                'password' => $account['account_password'],
                'roles' => $account['account_roles'] ?? [],
                'status' => UserStatus::Active->value,
            ]);

            $this->attach($employee, $user);

            return $user;
        });
    }

    /**
     * Link an existing account that has no employee yet. Requires permission to update that user.
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function linkAccount(User $actor, Employee $employee, User $user): void
    {
        if (! $actor->can('update', $user)) {
            throw new ActionNotAllowedException(__('You are not allowed to link this user account.'));
        }

        $this->ensureNotLinked($employee);

        if (Employee::withTrashed()->where('user_id', $user->id)->whereKeyNot($employee->id)->exists()) {
            throw ValidationException::withMessages(['link_user_id' => __('That user is already linked to another employee.')]);
        }

        DB::transaction(fn () => $this->attach($employee, $user));
    }

    /**
     * Remove the link to the login account. The account itself is never deleted.
     *
     * @throws ActionNotAllowedException
     */
    public function unlinkAccount(User $actor, Employee $employee): void
    {
        $user = $employee->user;

        if ($user === null) {
            return;
        }

        if (! $actor->can('update', $user)) {
            throw new ActionNotAllowedException(__('You are not allowed to unlink this user account.'));
        }

        DB::transaction(function () use ($employee, $user): void {
            $employee->user()->dissociate();
            $employee->saveQuietly();

            $this->audit->record(AuditEvent::EmployeeAccountUnlinked, $employee, "Unlinked {$user->email} from employee {$employee->full_name}", ['user' => $user->email], []);
        });
    }

    /**
     * The next number in the EMP-00001 sequence. Call it inside the transaction that saves the employee;
     * create() retries if a concurrent request wins the same number.
     */
    public function generateEmployeeNumber(): string
    {
        $last = Employee::withTrashed()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('employee_number');

        $next = is_string($last) && preg_match('/^EMP-(\d+)$/', $last, $matches) === 1 ? ((int) $matches[1]) + 1 : 1;

        while (Employee::withTrashed()->where('employee_number', sprintf('EMP-%05d', $next))->exists()) {
            $next++;
        }

        return sprintf('EMP-%05d', $next);
    }

    /**
     * Fail early, before anything is written, when the actor may not handle accounts the requested way.
     *
     * @param  EmployeeInput  $validated
     *
     * @throws ActionNotAllowedException
     */
    private function ensureCanHandleAccount(User $actor, string $mode, array $validated): void
    {
        if ($mode === 'create' && ! $actor->can('create', User::class)) {
            throw new ActionNotAllowedException(__('You are not allowed to create user accounts.'));
        }

        if ($mode === 'link') {
            $user = User::find($validated['link_user_id'] ?? null);

            if ($user === null || ! $actor->can('update', $user)) {
                throw new ActionNotAllowedException(__('You are not allowed to link this user account.'));
            }
        }
    }

    /**
     * @param  EmployeeInput  $validated
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    private function applyAccountMode(User $actor, Employee $employee, string $mode, array $validated): void
    {
        if ($mode === 'create') {
            $this->createAccount($actor, $employee, [
                'account_email' => (string) ($validated['account_email'] ?? ''),
                'account_password' => (string) ($validated['account_password'] ?? ''),
                'account_roles' => $validated['account_roles'] ?? [],
            ]);
        }

        if ($mode === 'link') {
            $this->linkAccount($actor, $employee, User::findOrFail($validated['link_user_id'] ?? null));
        }
    }

    /**
     * @throws ActionNotAllowedException
     */
    private function deactivateLinkedAccount(User $actor, Employee $employee): void
    {
        $user = $employee->user;

        if ($user === null || ! $user->isActive()) {
            return;
        }

        if (! $actor->can('changeStatus', $user)) {
            throw new ActionNotAllowedException(__('You are not allowed to deactivate the linked user account.'));
        }

        $this->users->setStatus($actor, $user, UserStatus::Inactive);
    }

    private function attach(Employee $employee, User $user): void
    {
        $employee->user()->associate($user);
        $employee->saveQuietly();

        $this->audit->record(AuditEvent::EmployeeAccountLinked, $employee, "Linked {$user->email} to employee {$employee->full_name}", [], ['user' => $user->email]);

        // Queued after the transaction commits.
        $user->notify(new EmployeeAccountLinkedNotification($employee->full_name, $employee->employee_number));
    }

    /**
     * @throws ValidationException
     */
    private function ensureNotLinked(Employee $employee): void
    {
        if ($employee->user_id !== null) {
            throw ValidationException::withMessages(['account_mode' => __('This employee already has a login account.')]);
        }
    }

    /**
     * Only the employee detail fields, never user_id, employee_number or the audit fields.
     *
     * @param  EmployeeInput  $validated
     * @return array<string, mixed>
     */
    private function details(array $validated): array
    {
        $details = [];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'phone', 'address', 'position', 'hire_date', 'status', 'notes'] as $field) {
            $value = $validated[$field] ?? null;
            $details[$field] = is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value);
        }

        return $details;
    }
}
