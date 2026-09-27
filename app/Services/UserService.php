<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PasswordChangedByAdminNotification;
use App\Notifications\RolesChangedNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * User accounts. Every method takes validated data only and enforces the rules of Task 03 §8 itself,
 * so they hold no matter which page (or crafted request) calls them.
 */
class UserService
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Create a self-registered account. The role always comes from configuration, never from the request.
     *
     * @param  array{name: string, email: string, password: string}  $validated
     */
    public function register(array $validated): User
    {
        $role = $this->defaultRegistrationRole();

        $user = DB::transaction(function () use ($validated, $role): User {
            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->status = UserStatus::Active;
            $user->save();

            $user->assignRole($role);

            return $user;
        });

        // Sends the verification email (Laravel's SendEmailVerificationNotification listener).
        event(new Registered($user));

        $user->notify(new WelcomeNotification((string) config('app.name')));
        Notification::send($this->userManagers()->reject(fn (User $manager): bool => $manager->is($user)), NewUserRegisteredNotification::for($user));

        return $user;
    }

    /**
     * Create an account on behalf of an administrator. Its email counts as verified.
     *
     * @param  array{name: string, email: string, password: string, roles?: list<string>, status: string}  $validated
     *
     * @throws ValidationException when the actor may not assign one of the roles
     */
    public function create(User $actor, array $validated): User
    {
        $roles = $this->assignableRolesOrFail($actor, $validated['roles'] ?? []);

        return DB::transaction(function () use ($validated, $roles): User {
            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->status = UserStatus::from($validated['status']);
            $user->email_verified_at = now();
            $user->save();

            $user->syncRoles($roles);

            if ($roles !== []) {
                $this->recordRoleChange($user, [], array_map(fn (Role $role): string => $role->name, $roles));
            }

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, roles?: list<string>, status: string}  $validated
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    public function update(User $actor, User $user, array $validated): User
    {
        $this->ensureCanManage($actor, $user);

        $status = UserStatus::from($validated['status']);
        $requestedRoles = collect($validated['roles'] ?? [])->unique()->sort()->values();
        $currentRoles = $user->getRoleNames()->sort()->values();
        $rolesChanged = $requestedRoles->all() !== $currentRoles->all();

        if ($actor->is($user) && ($rolesChanged || $status !== $user->status)) {
            throw new ActionNotAllowedException(__('You cannot change your own roles or status.'));
        }

        if ($rolesChanged) {
            $this->ensureCanChangeRoles($actor, $user, $currentRoles, $requestedRoles);
        }

        if ($status !== $user->status) {
            $this->ensureCanChangeStatus($user, $status);
        }

        return DB::transaction(function () use ($user, $validated, $status, $rolesChanged, $requestedRoles): User {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            $user->status = $status;

            if ($user->isDirty('email')) {
                // An administrator set the address, so it stays trusted.
                $user->email_verified_at = now();
            }

            $user->save();

            if ($rolesChanged) {
                $previous = $user->getRoleNames()->all();
                $user->syncRoles($requestedRoles->all());
                $this->recordRoleChange($user, $previous, $requestedRoles->all());

                // Queued after the transaction commits.
                $user->notify(new RolesChangedNotification($requestedRoles->values()->all()));
            }

            return $user;
        });
    }

    /**
     * Activate or deactivate an account. A deactivated user is signed out on their next request.
     *
     * @throws ActionNotAllowedException
     */
    public function setStatus(User $actor, User $user, UserStatus $status): User
    {
        $this->ensureCanManage($actor, $user);

        if ($actor->is($user)) {
            throw new ActionNotAllowedException(__('You cannot change your own status.'));
        }

        if ($status === $user->status) {
            return $user;
        }

        $this->ensureCanChangeStatus($user, $status);

        return DB::transaction(function () use ($user, $status): User {
            $previous = $user->status;
            $user->status = $status;
            // Saved quietly so the change is audited once, as a status change, not also as a generic update.
            $user->saveQuietly();

            $this->audit->record(
                AuditEvent::StatusChanged,
                $user,
                ($status === UserStatus::Active ? 'Activated ' : 'Deactivated ').$user->name,
                ['status' => $previous->value],
                ['status' => $status->value],
            );

            return $user;
        });
    }

    /**
     * Set a new password chosen by an administrator. The "remember me" token is rotated, so remembered
     * sessions on other devices end.
     *
     * @throws ActionNotAllowedException
     */
    public function resetPassword(User $actor, User $user, string $password): User
    {
        $this->ensureCanManage($actor, $user);

        return DB::transaction(function () use ($user, $password): User {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            // The event only; the password itself is never written to the audit trail.
            $this->audit->record(AuditEvent::PasswordReset, $user, "Set a new password for {$user->name}");

            $user->notify(new PasswordChangedByAdminNotification);

            return $user;
        });
    }

    /**
     * Delete an account. A linked employee record is kept; the foreign key clears its link.
     *
     * @throws ActionNotAllowedException
     */
    public function delete(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw new ActionNotAllowedException(__('You cannot delete your own account.'));
        }

        $this->ensureCanManage($actor, $user);

        if ($user->isSuperAdmin() && ($this->roles->isLastActiveSuperAdmin($user) || $this->superAdminCount() <= 1)) {
            throw new ActionNotAllowedException(__('The last Super Admin cannot be deleted.'));
        }

        DB::transaction(function () use ($user): void {
            $employee = $user->employee;

            if ($employee !== null) {
                // The foreign key clears the link; record it, since no model event sees that.
                $this->audit->record(AuditEvent::EmployeeAccountUnlinked, $employee, "Unlinked {$user->email} from employee {$employee->full_name} (account deleted)", ['user' => $user->email], []);
            }

            $user->syncRoles([]);
            $user->delete();
        });
    }

    /**
     * The role given to self-registered users (DEFAULT_REGISTRATION_ROLE). It must exist and can never
     * be the Super Admin role, so a misconfigured environment cannot hand out full access.
     */
    public function defaultRegistrationRole(): Role
    {
        $name = config('auth.registration.default_role');

        if (! is_string($name) || $name === '' || $name === RoleName::SuperAdmin->value) {
            throw new RuntimeException('DEFAULT_REGISTRATION_ROLE must name an existing role other than Super Admin.');
        }

        $role = Role::query()->where('name', $name)->where('guard_name', 'web')->first();

        if ($role === null) {
            throw new RuntimeException("The default registration role \"{$name}\" does not exist. Run the RolePermissionSeeder.");
        }

        return $role;
    }

    /**
     * Only a Super Admin may manage a Super Admin.
     *
     * @throws ActionNotAllowedException
     */
    private function ensureCanManage(User $actor, User $user): void
    {
        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw new ActionNotAllowedException(__('Only a Super Admin can manage a Super Admin.'));
        }
    }

    /**
     * Every role added or removed must be one the actor may assign, and the last active Super Admin keeps the role.
     *
     * @param  Collection<int, string>  $current
     * @param  Collection<int, string>  $requested
     *
     * @throws ActionNotAllowedException|ValidationException
     */
    private function ensureCanChangeRoles(User $actor, User $user, Collection $current, Collection $requested): void
    {
        $changed = $requested->diff($current)->merge($current->diff($requested))->unique()->values();

        $this->assignableRolesOrFail($actor, $changed->all());

        $losesSuperAdmin = $current->contains(RoleName::SuperAdmin->value) && ! $requested->contains(RoleName::SuperAdmin->value);

        if ($losesSuperAdmin && $this->roles->isLastActiveSuperAdmin($user)) {
            throw new ActionNotAllowedException(__('The last active Super Admin cannot be demoted.'));
        }
    }

    /**
     * @throws ActionNotAllowedException
     */
    private function ensureCanChangeStatus(User $user, UserStatus $status): void
    {
        if ($status === UserStatus::Inactive && $this->roles->isLastActiveSuperAdmin($user)) {
            throw new ActionNotAllowedException(__('The last active Super Admin cannot be deactivated.'));
        }
    }

    /**
     * Load the named roles and make sure the actor may assign every one of them.
     *
     * @param  array<int, string>  $names
     * @return list<Role>
     *
     * @throws ValidationException
     */
    private function assignableRolesOrFail(User $actor, array $names): array
    {
        $roles = [];

        foreach (array_unique($names) as $name) {
            $role = Role::query()->with('permissions')->where('name', $name)->where('guard_name', 'web')->first();

            if ($role === null || ! $this->roles->canAssign($actor, $role)) {
                throw ValidationException::withMessages([
                    'roles' => __('You are not allowed to assign the :role role.', ['role' => $name]),
                ]);
            }

            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * @param  array<int, string>  $old
     * @param  array<int, string>  $new
     */
    private function recordRoleChange(User $user, array $old, array $new): void
    {
        sort($old);
        sort($new);

        $this->audit->record(AuditEvent::RolesSynced, $user, "Changed the roles of {$user->name}", ['roles' => $old], ['roles' => $new]);
    }

    /**
     * Active users who may see the user list (users.view, or the Super Admin bypass).
     *
     * @return Collection<int, User>
     */
    private function userManagers(): Collection
    {
        return User::query()
            ->active()
            ->where(fn ($query) => $query
                ->whereHas('roles', fn ($roles) => $roles->where('name', RoleName::SuperAdmin->value)
                    ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', PermissionName::UsersView->value)))
                ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', PermissionName::UsersView->value)))
            ->get()
            ->toBase();
    }

    private function superAdminCount(): int
    {
        return User::role(RoleName::SuperAdmin->value)->count();
    }
}
