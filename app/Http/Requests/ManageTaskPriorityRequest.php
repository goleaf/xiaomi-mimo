<?php

namespace App\Http\Requests;

use App\Models\TaskPriority;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageTaskPriorityRequest extends FormRequest
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
        return [
            'operation' => ['required', Rule::in(['archive', 'restore', 'set_default'])],
        ];
    }

    public function operation(): string
    {
        return $this->string('operation')->toString();
    }
}
