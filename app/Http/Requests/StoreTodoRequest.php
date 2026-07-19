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

    /** @return array<string, mixed> */
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

    /**
     * @return array{title: string, project_id?: string|null, assigned_to?: string|null, parent_id?: string|null, description?: string|null, status?: string, priority?: string, due_date?: string|null, start_date?: string|null, estimated_time?: int|null, label_ids?: list<string>, tag_ids?: list<string>}
     */
    public function todoData(): array
    {
        $data = ['title' => $this->string('title')->toString()];

        foreach (['project_id', 'assigned_to', 'parent_id', 'description', 'due_date', 'start_date'] as $key) {
            $value = $this->validated($key);
            $data[$key] = is_string($value) ? $value : null;
        }

        foreach (['status', 'priority'] as $key) {
            $value = $this->validated($key);

            if (is_string($value)) {
                $data[$key] = $value;
            }
        }

        $estimatedTime = $this->validated('estimated_time');
        $data['estimated_time'] = is_int($estimatedTime) ? $estimatedTime : null;

        foreach (['label_ids', 'tag_ids'] as $key) {
            $value = $this->validated($key);

            if (is_array($value)) {
                $data[$key] = array_values(array_filter($value, is_string(...)));
            }
        }

        return $data;
    }
}
