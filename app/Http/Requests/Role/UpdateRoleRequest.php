<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && ($this->user()?->can('update', $role) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return self::rulesFor($role);
    }

    /**
     * Shared by this request and the Livewire edit page. A system role keeps its name (RoleService rejects a rename).
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(Role $role): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\pN][\pL\pN .\'&-]*$/u', Rule::unique(Role::class, 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return StoreRoleRequest::fieldMessages();
    }
}
