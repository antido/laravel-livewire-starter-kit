<?php

declare(strict_types=1);

namespace App\Http\Requests\Permission;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    /**
     * The "module.action" format, lowercase letters and underscores only.
     */
    public const string FORMAT = '/^[a-z_]+\.[a-z_]+$/';

    public function authorize(): bool
    {
        return $this->user()?->can('create', Permission::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::fieldMessages();
    }

    /**
     * Shared by this request and the Privileges page. Pass the permission being renamed to ignore its own name.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?Permission $ignore = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:'.self::FORMAT,
                Rule::unique(Permission::class, 'name')->where('guard_name', 'web')->ignore($ignore?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fieldMessages(): array
    {
        return [
            'name.regex' => 'Use the module.action format with lowercase letters and underscores, for example reports_export.run.',
        ];
    }
}
