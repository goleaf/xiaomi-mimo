<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['all', 'unread'])],
            'per_page' => ['sometimes', 'integer', Rule::in([20, 50])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function status(): string
    {
        $status = $this->validated('status');

        return is_string($status) ? $status : 'all';
    }

    public function perPage(): int
    {
        $perPage = $this->validated('per_page');

        return is_int($perPage) ? $perPage : 20;
    }
}
