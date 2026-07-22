<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\Tag;
use App\Models\Workspace;
use App\Services\RecurrenceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTodoRequest extends FormRequest
{
    /** @var list<string> */
    public const array RECURRING_RULES = RecurrenceSchedule::RULES;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : '';

        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'project_id' => [
                'nullable', 'uuid',
                Rule::exists('projects', 'id')->where('workspace_id', $workspaceId),
            ],
            'assigned_to' => [
                'nullable', 'uuid',
                Rule::exists('workspace_members', 'user_id')->where('workspace_id', $workspaceId),
            ],
            'parent_id' => [
                'nullable', 'uuid',
                Rule::exists('todos', 'id')->where('workspace_id', $workspaceId),
            ],
            'status' => [
                'sometimes', 'string', 'prohibits:status_id',
                Rule::exists('task_statuses', 'key')
                    ->where('workspace_id', $workspaceId)
                    ->where('is_archived', 0),
            ],
            'status_id' => [
                'sometimes', 'uuid', 'prohibits:status',
                Rule::exists('task_statuses', 'id')
                    ->where('workspace_id', $workspaceId)
                    ->where('is_archived', 0),
            ],
            'priority' => [
                'sometimes', 'string', 'prohibits:priority_id',
                Rule::exists('task_priorities', 'key')
                    ->where('workspace_id', $workspaceId)
                    ->where('is_archived', 0),
            ],
            'priority_id' => [
                'sometimes', 'uuid', 'prohibits:priority',
                Rule::exists('task_priorities', 'id')
                    ->where('workspace_id', $workspaceId)
                    ->where('is_archived', 0),
            ],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'before_or_equal:due_date'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurring_rule' => [
                'exclude_unless:is_recurring,true',
                'required',
                'string',
                Rule::in(self::RECURRING_RULES),
            ],
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

    /**
     * @return array{title: string, project_id?: string|null, assigned_to?: string|null, parent_id?: string|null, description?: string|null, status?: string, status_id?: string, priority?: string, priority_id?: string, due_date?: string|null, start_date?: string|null, estimated_time?: int|null, is_recurring: bool, recurring_rule: string|null, label_ids?: list<string>, tag_ids?: list<string>}
     */
    public function todoData(): array
    {
        $data = ['title' => $this->string('title')->toString()];

        foreach (['project_id', 'assigned_to', 'parent_id', 'description', 'due_date', 'start_date'] as $key) {
            $value = $this->validated($key);
            $data[$key] = is_string($value) ? $value : null;
        }

        foreach (['status', 'status_id', 'priority', 'priority_id'] as $key) {
            $value = $this->validated($key);

            if (is_string($value)) {
                $data[$key] = $value;
            }
        }

        $estimatedTime = $this->validated('estimated_time');
        $data['estimated_time'] = is_int($estimatedTime) ? $estimatedTime : null;
        $data['is_recurring'] = $this->boolean('is_recurring');
        $recurringRule = $this->validated('recurring_rule');
        $data['recurring_rule'] = $data['is_recurring'] && is_string($recurringRule)
            ? $recurringRule
            : null;

        foreach (['label_ids', 'tag_ids'] as $key) {
            $value = $this->validated($key);

            if (is_array($value)) {
                $data[$key] = array_values(array_filter($value, is_string(...)));
            }
        }

        return $data;
    }
}
