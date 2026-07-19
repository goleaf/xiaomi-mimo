<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'color' => ['sometimes', 'string', 'max:7'],
            'icon' => ['sometimes', 'string', 'max:50'],
        ];
    }

    /** @return array{name: string, description?: string|null, color?: string, icon?: string} */
    public function projectData(): array
    {
        $data = ['name' => $this->string('name')->toString()];
        $description = $this->validated('description');
        $color = $this->validated('color');
        $icon = $this->validated('icon');

        if (is_string($description) || is_null($description)) {
            $data['description'] = $description;
        }

        if (is_string($color)) {
            $data['color'] = $color;
        }

        if (is_string($icon)) {
            $data['icon'] = $icon;
        }

        return $data;
    }
}
