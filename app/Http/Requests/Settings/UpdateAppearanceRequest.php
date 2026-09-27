<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\ThemePreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Shared by this request, the Appearance page and the header toggle. Only enum values are accepted.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'theme' => ['required', 'string', Rule::enum(ThemePreference::class)],
        ];
    }
}
