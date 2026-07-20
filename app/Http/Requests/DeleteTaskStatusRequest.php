<?php

namespace App\Http\Requests;

use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $status = $this->route('taskStatus');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $status instanceof TaskStatus
            && $status->workspace_id === $workspace->id
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $status = $this->route('taskStatus');

        return [
            'replacement_id' => [
                'nullable',
                'uuid',
                Rule::exists('task_statuses', 'id')->where(
                    fn ($query) => $query
                        ->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : '')
                        ->where('is_archived', false)
                        ->where('id', '!=', $status instanceof TaskStatus ? $status->id : ''),
                ),
            ],
        ];
    }

    public function replacement(): ?TaskStatus
    {
        $id = $this->validated('replacement_id');

        return is_string($id) ? TaskStatus::query()->findOrFail($id) : null;
    }
}
