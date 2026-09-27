<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Staff role is now called User. The role is renamed in place, so everyone who had it keeps it,
 * with the same permissions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->rename('Staff', RoleName::User->value);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rename(RoleName::User->value, 'Staff');
    }

    private function rename(string $from, string $to): void
    {
        $role = Role::query()->where('name', $from)->where('guard_name', 'web')->first();

        if ($role === null || Role::query()->where('name', $to)->where('guard_name', 'web')->exists()) {
            return;
        }

        DB::transaction(function () use ($role, $from, $to): void {
            $role->name = $to;
            $role->save();

            app(AuditLogService::class)->record(AuditEvent::Updated, $role, "Renamed role {$from} to {$to}", ['name' => $from], ['name' => $to]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
