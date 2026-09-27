<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\SuperAdminService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the first Super Admin from SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD.
 * If that account already exists, it only makes sure it keeps the Super Admin role.
 */
class AdminUserSeeder extends Seeder
{
    public function run(SuperAdminService $superAdmins): void
    {
        $settings = [
            'SUPER_ADMIN_NAME' => config('app.super_admin.name'),
            'SUPER_ADMIN_EMAIL' => config('app.super_admin.email'),
            'SUPER_ADMIN_PASSWORD' => config('app.super_admin.password'),
        ];

        $values = array_filter($settings, fn (mixed $value): bool => is_string($value) && trim($value) !== '');
        $missing = array_keys(array_diff_key($settings, $values));

        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'Cannot create the Super Admin: set %s in your .env file, or run "php artisan app:create-super-admin" instead.',
                implode(', ', $missing),
            ));
        }

        $email = mb_strtolower(trim((string) $values['SUPER_ADMIN_EMAIL']));
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->assignRole(RoleName::SuperAdmin->value);
            $this->command->info("The Super Admin {$existing->email} already exists.");

            return;
        }

        $user = $superAdmins->create([
            'name' => trim((string) $values['SUPER_ADMIN_NAME']),
            'email' => $email,
            'password' => (string) $values['SUPER_ADMIN_PASSWORD'],
        ]);

        $this->command->info("Created the Super Admin {$user->email}.");
    }
}
