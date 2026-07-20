<?php

namespace App\Http\Requests;

use App\Models\TaskPriority;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTaskPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $priority = $this->route('taskPriority');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $priority instanceof TaskPriority
            && $priority->workspace_id === $workspace->id
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $priority = $this->route('taskPriority');

        return [
            'replacement_id' => [
                'nullable',
                'uuid',
                Rule::exists('task_priorities', 'id')->where(
                    fn ($query) => $query
                        ->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : '')
                        ->where('is_archived', false)
                        ->where('id', '!=', $priority instanceof TaskPriority ? $priority->id : ''),
                ),
            ],
        ];
    }

    public function replacement(): ?TaskPriority
    {
        $id = $this->validated('replacement_id');

        return is_string($id) ? TaskPriority::query()->findOrFail($id) : null;
    }
}
