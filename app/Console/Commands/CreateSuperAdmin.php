<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SuperAdminService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-super-admin')]
#[Description('Create a Super Admin account (asks for the name, email and a hidden password)')]
class CreateSuperAdmin extends Command
{
    public function handle(SuperAdminService $superAdmins): int
    {
        $name = text(label: 'Name', required: true);
        $email = mb_strtolower(text(label: 'Email address', required: true));
        $password = password(label: 'Password', required: true, hint: 'At least 8 characters.');
        $confirmation = password(label: 'Confirm password', required: true);

        if (! hash_equals($password, $confirmation)) {
            $this->components->error('The passwords do not match.');

            return self::FAILURE;
        }

        try {
            $user = $superAdmins->create(['name' => $name, 'email' => $email, 'password' => $password]);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $this->components->info("Created the Super Admin {$user->email}.");

        return self::SUCCESS;
    }
}
