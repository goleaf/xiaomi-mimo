<?php

namespace App\Http\Requests;

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
            'project_id' => [
                'nullable', 'uuid',
                Rule::exists('projects', 'id')->where('workspace_id', $workspaceId),
            ],
            'assigned_to' => [
                'nullable', 'uuid',
                Rule::exists('workspace_members', 'user_id')->where('workspace_id', $workspaceId),
            ],
            'status' => [
                'sometimes', 'string', 'prohibits:status_id',
                Rule::exists('task_statuses', 'key')
                    ->where('workspace_id', $workspaceId)
                    ->where(function ($query) use ($todo): void {
                        $query->where('is_archived', 0);

                        if ($todo instanceof Todo && is_string($todo->status_id)) {
                            $query->orWhere('id', $todo->status_id);
                        }
                    }),
            ],
            'status_id' => [
                'sometimes', 'uuid', 'prohibits:status',
                Rule::exists('task_statuses', 'id')
                    ->where('workspace_id', $workspaceId)
                    ->where(function ($query) use ($todo): void {
                        $query->where('is_archived', 0);

                        if ($todo instanceof Todo && is_string($todo->status_id)) {
                            $query->orWhere('id', $todo->status_id);
                        }
                    }),
            ],
            'priority' => [
                'sometimes', 'string', 'prohibits:priority_id',
                Rule::exists('task_priorities', 'key')
                    ->where('workspace_id', $workspaceId)
                    ->where(function ($query) use ($todo): void {
                        $query->where('is_archived', 0);

                        if ($todo instanceof Todo && is_string($todo->priority_id)) {
                            $query->orWhere('id', $todo->priority_id);
                        }
                    }),
            ],
            'priority_id' => [
                'sometimes', 'uuid', 'prohibits:priority',
                Rule::exists('task_priorities', 'id')
                    ->where('workspace_id', $workspaceId)
                    ->where(function ($query) use ($todo): void {
                        $query->where('is_archived', 0);

                        if ($todo instanceof Todo && is_string($todo->priority_id)) {
                            $query->orWhere('id', $todo->priority_id);
                        }
                    }),
            ],
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
