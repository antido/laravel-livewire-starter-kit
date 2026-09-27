<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('resetPassword', $user) ?? false);
    }

    /**
     * Shared by this request and the Livewire reset-password page.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
        ];
    }
}
