<?php

namespace App\Http\Requests;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderChecklistsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');

        return $user instanceof User
            && $todo instanceof Todo
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $todo = $this->route('todo');

        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => [
                'required', 'uuid', 'distinct',
                Rule::exists('checklists', 'id')->where(
                    'todo_id',
                    $todo instanceof Todo ? $todo->id : '',
                ),
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
