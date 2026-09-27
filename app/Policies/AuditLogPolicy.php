<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\User;

/**
 * The audit trail is read-only: there are no create, update or delete abilities.
 */
class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::AuditTrailsView->value);
    }

    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $actor->can(PermissionName::AuditTrailsView->value);
    }
}
