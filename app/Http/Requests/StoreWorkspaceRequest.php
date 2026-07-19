<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array{name: string, description?: string|null} */
    public function workspaceData(): array
    {
        $description = $this->validated('description');

        return [
            'name' => $this->string('name')->toString(),
            'description' => is_string($description) ? $description : null,
        ];
    }
}
