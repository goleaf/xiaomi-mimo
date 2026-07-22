<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('update', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'uuid',
                'distinct',
                Rule::exists('todos', 'id')
                    ->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : '')
                    ->whereNull('deleted_at'),
            ],
            'action' => ['required', 'string', 'in:complete,uncomplete,archive,restore,delete'],
        ];
    }

    /** @return list<string> */
    public function ids(): array
    {
        /** @var list<string> */
        return $this->validated('ids');
    }

    public function action(): string
    {
        return $this->string('action')->toString();
    }
}
