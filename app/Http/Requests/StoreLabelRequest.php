<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->workspace();

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            $workspace = $this->workspace();
            $label = $this->route('label');
            $query = Label::query()
                ->where('workspace_id', $workspace?->id)
                ->where('normalized_name', Label::normalizeName($this->string('name')->toString()));

            if ($label instanceof Label) {
                $query->whereKeyNot($label->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => 'name']));
            }

            if (! $label instanceof Label
                && $workspace instanceof Workspace
                && $workspace->labels()->count() >= Label::MAX_PER_WORKSPACE) {
                $validator->errors()->add(
                    'name',
                    __('ui.workspaces.management.configuration.labels.limit_reached', [
                        'count' => Label::MAX_PER_WORKSPACE,
                    ]),
                );
            }
        }];
    }

    public function name(): string
    {
        return $this->string('name')->toString();
    }

    public function color(): string
    {
        return $this->string('color', '#6366f1')->toString();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => $this->string('name')->trim()->toString()]);
        }
    }

    private function workspace(): ?Workspace
    {
        $workspace = $this->route('workspace');

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        $label = $this->route('label');

        return $label instanceof Label ? $label->workspace : null;
    }
}
