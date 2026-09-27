<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee && ($this->user()?->can('update', $employee) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return self::rulesFor($employee);
    }

    /**
     * Shared by this request and the Livewire edit page. An employee who already has an account keeps it
     * here ("keep"); unlinking is a separate action.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(Employee $employee): array
    {
        $modes = $employee->user_id !== null ? ['keep'] : StoreEmployeeRequest::ACCOUNT_MODES;

        return array_merge(StoreEmployeeRequest::detailRules(), [
            'account_mode' => ['required', Rule::in($modes)],
            'account_email' => ['nullable', 'required_if:account_mode,create', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'account_password' => ['nullable', 'required_if:account_mode,create', 'string', 'max:255', 'confirmed', Password::defaults()],
            'account_roles' => ['array'],
            'account_roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'link_user_id' => ['nullable', 'required_if:account_mode,link', 'integer', Rule::exists(User::class, 'id'), Rule::unique(Employee::class, 'user_id')->ignore($employee->id)],
            'deactivate_account' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return StoreEmployeeRequest::fieldMessages();
    }
}
