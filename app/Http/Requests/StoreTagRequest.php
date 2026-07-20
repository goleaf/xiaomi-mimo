<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTagRequest extends FormRequest
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
        return ['name' => ['required', 'string', 'max:100']];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            $workspace = $this->workspace();
            $tag = $this->route('tag');
            $query = Tag::query()
                ->where('workspace_id', $workspace?->id)
                ->where('normalized_name', Tag::normalizeName($this->string('name')->toString()));

            if ($tag instanceof Tag) {
                $query->whereKeyNot($tag->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => 'name']));
            }

            if (! $tag instanceof Tag
                && $workspace instanceof Workspace
                && $workspace->tags()->count() >= Tag::MAX_PER_WORKSPACE) {
                $validator->errors()->add(
                    'name',
                    __('ui.workspaces.management.configuration.tags.limit_reached', [
                        'count' => Tag::MAX_PER_WORKSPACE,
                    ]),
                );
            }
        }];
    }

    public function name(): string
    {
        return $this->string('name')->toString();
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

        $tag = $this->route('tag');

        return $tag instanceof Tag ? $tag->workspace : null;
    }
}
