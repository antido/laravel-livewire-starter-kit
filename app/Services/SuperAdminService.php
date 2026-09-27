<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Creates the initial Super Admin for the seeder and the app:create-super-admin command.
 */
class SuperAdminService
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string}  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): User
    {
        $validated = Validator::make($data, self::rules())->validate();

        return DB::transaction(function () use ($validated): User {
            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->save();

            $user->assignRole(RoleName::SuperAdmin->value);

            return $user;
        });
    }
}
