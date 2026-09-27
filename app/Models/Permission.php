<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PermissionCollection;
use App\Enums\PermissionName;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * The app's permission model (config/permission.php points Spatie here), so it can have a policy
 * and a collection that groups permissions by module.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 */
#[CollectedBy(PermissionCollection::class)]
class Permission extends SpatiePermission
{
    public function isSystem(): bool
    {
        return PermissionName::isSystem($this->name);
    }

    public function module(): string
    {
        return PermissionName::moduleOf($this->name);
    }

    public function action(): string
    {
        return PermissionName::actionOf($this->name);
    }
}
