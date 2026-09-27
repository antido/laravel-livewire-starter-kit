<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Shared by this request and the Livewire create page. Whether the actor may assign each role
     * is checked in UserService.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}
