<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid', 'exists:todos,id'],
            'action' => ['required', 'string', 'in:complete,uncomplete,archive,restore,delete'],
        ];
    }
}
