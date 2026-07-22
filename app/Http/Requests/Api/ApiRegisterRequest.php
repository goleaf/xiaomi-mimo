<?php

namespace App\Http\Requests\Api;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ApiRegisterRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'device_name' => trim((string) $this->input('device_name')),
        ]);
    }
}
