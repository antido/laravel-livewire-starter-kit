<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return self::rulesFor($user);
    }

    /**
     * Shared by this request and the Livewire edit page. The email must be unique except for this user.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'roles' => ['array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}
