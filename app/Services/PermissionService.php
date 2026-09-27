<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Custom permissions ("privileges"). The system permissions from PermissionName can never be renamed or deleted.
 */
class PermissionService
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array{name: string}  $validated
     */
    public function create(User $actor, array $validated): Permission
    {
        $permission = DB::transaction(function () use ($validated): Permission {
            $permission = new Permission(['name' => $validated['name'], 'guard_name' => 'web']);
            $permission->save();

            $this->audit->record(AuditEvent::Created, $permission, "Created privilege {$permission->name}", [], ['name' => $permission->name]);

            return $permission;
        });

        $this->roles->forgetCachedPermissions();

        return $permission;
    }

    /**
     * @param  array{name: string}  $validated
     *
     * @throws ActionNotAllowedException
     */
    public function rename(User $actor, Permission $permission, array $validated): Permission
    {
        $this->ensureCustom($permission, __('System permissions cannot be renamed.'));

        DB::transaction(function () use ($permission, $validated): void {
            $oldName = $permission->name;
            $permission->name = $validated['name'];
            $permission->save();

            $this->audit->record(AuditEvent::Updated, $permission, "Renamed privilege {$oldName} to {$permission->name}", ['name' => $oldName], ['name' => $permission->name]);
        });

        $this->roles->forgetCachedPermissions();

        return $permission;
    }

    /**
     * Delete a custom permission. One that is still assigned to a role is not deleted: remove it from
     * those roles first. (This starter kit blocks the deletion rather than asking for a confirmation.)
     *
     * @throws ActionNotAllowedException
     */
    public function delete(User $actor, Permission $permission): void
    {
        $this->ensureCustom($permission, __('System permissions cannot be deleted.'));

        $roles = $permission->roles()->pluck('name');

        if ($roles->isNotEmpty()) {
            throw new ActionNotAllowedException(__('This privilege is used by :roles. Remove it from those roles first.', [
                'roles' => $roles->implode(', '),
            ]));
        }

        DB::transaction(function () use ($permission): void {
            $this->audit->record(AuditEvent::Deleted, $permission, "Deleted privilege {$permission->name}", ['name' => $permission->name]);

            $permission->delete();
        });

        $this->roles->forgetCachedPermissions();
    }

    /**
     * @throws ActionNotAllowedException
     */
    private function ensureCustom(Permission $permission, string $message): void
    {
        if ($permission->isSystem()) {
            throw new ActionNotAllowedException($message);
        }
    }
}
