<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTodosRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.id' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('todos', 'id')
                    ->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : ''),
            ],
            'items.*.position' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return list<array{id: string, position: int}> */
    public function items(): array
    {
        /** @var list<array{id: string, position: int}> */
        return $this->validated('items');
    }
}
