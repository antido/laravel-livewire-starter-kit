<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * Shared by this request and the Livewire create page. Whether the actor may grant each permission
     * is checked in RoleService.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\pN][\pL\pN .\'&-]*$/u', Rule::notIn(RoleName::values()), Rule::unique(Role::class, 'name')->where('guard_name', 'web')],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::fieldMessages();
    }

    /**
     * Shared with the Livewire pages.
     *
     * @return array<string, string>
     */
    public static function fieldMessages(): array
    {
        return [
            'name.regex' => 'Use letters, numbers, spaces and . \' & - only.',
            'name.not_in' => 'That name belongs to a system role.',
        ];
    }
}
