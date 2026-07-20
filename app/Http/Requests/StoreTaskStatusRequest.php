<?php

namespace App\Http\Requests;

use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskStatusRequest extends FormRequest
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
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = $this->route('workspace');
            $status = $this->route('taskStatus');

            if (! $workspace instanceof Workspace || $validator->errors()->has('name')) {
                return;
            }

            $query = $workspace->taskStatuses()
                ->where('normalized_name', TaskStatus::normalizeName($this->string('name')->toString()));

            if ($status instanceof TaskStatus) {
                $query->whereKeyNot($status->id);
            } elseif ($workspace->taskStatuses()->count() >= TaskStatus::MAX_PER_WORKSPACE) {
                $validator->errors()->add('name', __('validation.max.array', [
                    'attribute' => 'statuses',
                    'max' => TaskStatus::MAX_PER_WORKSPACE,
                ]));
            }

            if ($query->exists()) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => 'name']));
            }
        }];
    }

    /** @return array{name: string, color: string, is_completed?: bool} */
    public function definitionData(): array
    {
        /** @var array{name: string, color: string, is_completed?: bool} */
        return $this->safe()->only(['name', 'color', 'is_completed']);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => $this->string('name')->trim()->toString()]);
        }
    }
}
