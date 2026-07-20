<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTaskPrioritiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'ids' => ['required', 'array'],
            'ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('task_priorities', 'id')
                    ->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : ''),
            ],
        ];
    }

    /** @return list<string> */
    public function ids(): array
    {
        /** @var list<string> */
        return $this->validated('ids');
    }
}
