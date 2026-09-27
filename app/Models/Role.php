<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The app's role model (config/permission.php points Spatie here), so it can have a policy.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 */
class Role extends SpatieRole
{
    public function isSystem(): bool
    {
        return RoleName::isSystem($this->name);
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === RoleName::SuperAdmin->value;
    }
}
