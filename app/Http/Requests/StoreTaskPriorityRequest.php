<?php

namespace App\Http\Requests;

use App\Models\TaskPriority;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskPriorityRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = $this->route('workspace');
            $priority = $this->route('taskPriority');

            if (! $workspace instanceof Workspace || $validator->errors()->has('name')) {
                return;
            }

            $query = $workspace->taskPriorities()
                ->where('normalized_name', TaskPriority::normalizeName($this->string('name')->toString()));

            if ($priority instanceof TaskPriority) {
                $query->whereKeyNot($priority->id);
            } elseif ($workspace->taskPriorities()->count() >= TaskPriority::MAX_PER_WORKSPACE) {
                $validator->errors()->add('name', __('validation.max.array', [
                    'attribute' => 'priorities',
                    'max' => TaskPriority::MAX_PER_WORKSPACE,
                ]));
            }

            if ($query->exists()) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => 'name']));
            }
        }];
    }

    /** @return array{name: string, color: string} */
    public function definitionData(): array
    {
        /** @var array{name: string, color: string} */
        return $this->safe()->only(['name', 'color']);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => $this->string('name')->trim()->toString()]);
        }
    }
}
