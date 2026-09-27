<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Cashier role is no longer part of the kit. Existing databases lose it here; anyone who held it
 * moves to Staff, which has the same permissions (dashboard.view), so nobody loses access.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cashier = Role::query()->where('name', 'Cashier')->where('guard_name', 'web')->first();

        if ($cashier === null) {
            return;
        }

        DB::transaction(function () use ($cashier): void {
            // The role was still called Staff when this migration was written (it is renamed to User later).
            $staff = Role::findOrCreate('Staff', 'web');

            foreach (User::role($cashier)->get() as $user) {
                if (! $user->hasRole($staff)) {
                    $user->assignRole($staff);
                }

                $user->removeRole($cashier);

                app(AuditLogService::class)->record(
                    AuditEvent::RolesSynced,
                    $user,
                    "Moved {$user->name} from the removed Cashier role to Staff",
                    ['roles' => ['Cashier']],
                    ['roles' => ['Staff']],
                );
            }

            $cashier->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations: bring the role back (users moved to Staff stay there).
     */
    public function down(): void
    {
        $role = Role::findOrCreate('Cashier', 'web');
        $role->givePermissionTo('dashboard.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
