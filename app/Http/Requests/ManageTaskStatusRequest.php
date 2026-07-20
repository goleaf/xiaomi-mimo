<?php

namespace App\Http\Requests;

use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageTaskStatusRequest extends FormRequest
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
        return [
            'operation' => ['required', Rule::in([
                'archive', 'restore', 'set_default', 'set_completion_target',
            ])],
        ];
    }

    public function operation(): string
    {
        return $this->string('operation')->toString();
    }
}
