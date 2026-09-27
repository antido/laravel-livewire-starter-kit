<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * How the optional login account is handled.
     */
    public const array ACCOUNT_MODES = ['none', 'create', 'link'];

    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * Shared by this request and the Livewire employee form. user_id, employee_number, created_by and
     * updated_by are deliberately absent: the server sets them. Whether the actor may create or link
     * an account is checked in EmployeeService.
     *
     * @return array<string, list<mixed>>
     */
    public static function fieldRules(): array
    {
        return array_merge(self::detailRules(), [
            'account_mode' => ['required', Rule::in(self::ACCOUNT_MODES)],
            'account_email' => ['nullable', 'required_if:account_mode,create', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'account_password' => ['nullable', 'required_if:account_mode,create', 'string', 'max:255', 'confirmed', Password::defaults()],
            'account_roles' => ['array'],
            'account_roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'link_user_id' => ['nullable', 'required_if:account_mode,link', 'integer', Rule::exists(User::class, 'id'), Rule::unique(Employee::class, 'user_id')],
        ]);
    }

    /**
     * The employee details, shared with UpdateEmployeeRequest.
     *
     * @return array<string, list<mixed>>
     */
    public static function detailRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]{7,30}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
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
     * @return array<string, string>
     */
    public static function fieldMessages(): array
    {
        return [
            'phone.regex' => 'Use digits, spaces and + ( ) - only.',
            'link_user_id.unique' => 'That user is already linked to another employee.',
            'link_user_id.required_if' => 'Choose the user to link.',
            'account_email.required_if' => 'Enter the login email for the new account.',
            'account_password.required_if' => 'Enter a password for the new account.',
        ];
    }
}
