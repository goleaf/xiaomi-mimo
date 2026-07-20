<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class DetachTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $todo = $this->route('todo');
        $tag = $this->route('tag');

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || ! $todo instanceof Todo
            || ! $tag instanceof Tag) {
            return false;
        }

        $todo->setRelation('workspace', $workspace);

        return $todo->workspace_id === $workspace->id
            && $tag->workspace_id === $workspace->id
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
