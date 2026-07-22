<?php

namespace App\Http\Requests;

use App\Enums\UserLanguage;
use App\Models\UserPreference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'language' => ['sometimes', Rule::enum(UserLanguage::class)],
            'date_format' => ['sometimes', Rule::in(UserPreference::DATE_FORMATS)],
            'time_format' => ['sometimes', Rule::in(UserPreference::TIME_FORMATS)],
            'theme' => ['sometimes', Rule::in(['system', 'light', 'dark'])],
            'default_view' => ['sometimes', Rule::in(UserPreference::DEFAULT_VIEWS)],
            'start_page' => ['sometimes', Rule::in(UserPreference::START_PAGES)],
            'notification_email' => ['sometimes', 'boolean'],
            'notification_browser' => ['sometimes', 'boolean'],
            'notification_in_app' => ['sometimes', 'boolean'],
        ];
    }
}
