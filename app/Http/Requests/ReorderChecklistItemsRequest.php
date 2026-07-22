<?php

namespace App\Http\Requests;

use App\Models\Checklist;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderChecklistItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');
        $checklist = $this->route('checklist');

        if ($checklist instanceof Checklist) {
            $todo = $checklist->todo;
        }

        return $user instanceof User
            && $todo instanceof Todo
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $checklist = $this->route('checklist');

        return [
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => [
                'required', 'uuid', 'distinct',
                Rule::exists('checklist_items', 'id')->where(
                    'checklist_id',
                    $checklist instanceof Checklist ? $checklist->id : '',
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
