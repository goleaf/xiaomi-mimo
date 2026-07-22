<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'device_name' => trim((string) $this->input('device_name')),
        ]);
    }
}
