<?php

namespace App\Http\Requests;

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('todo'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $todo = $this->route('todo');
        $workspaceId = $todo instanceof Todo ? $todo->workspace_id : '';

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
            'label_ids.*' => [
                'uuid',
                Rule::exists(Label::class, 'id')->where('workspace_id', $workspaceId),
            ],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => [
                'uuid',
                Rule::exists(Tag::class, 'id')->where('workspace_id', $workspaceId),
            ],
        ];
    }
}
