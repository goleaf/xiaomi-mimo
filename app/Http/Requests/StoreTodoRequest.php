<?php

namespace App\Http\Requests;

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:todos,id'],
            'status' => ['sometimes', Rule::enum(TodoStatus::class)],
            'priority' => ['sometimes', Rule::enum(TodoPriority::class)],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'before_or_equal:due_date'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['uuid', 'exists:labels,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
