<?php

namespace App\Http\Requests;

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('todo'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'status' => ['sometimes', Rule::enum(TodoStatus::class)],
            'priority' => ['sometimes', Rule::enum(TodoPriority::class)],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'spent_time' => ['nullable', 'integer', 'min:0'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['uuid', 'exists:labels,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
