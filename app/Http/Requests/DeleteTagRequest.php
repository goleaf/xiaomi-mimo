<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class DeleteTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $tag = $this->route('tag');

        if (! $workspace instanceof Workspace && $tag instanceof Tag) {
            $workspace = $tag->workspace;
        }

        return $user instanceof User
            && $workspace instanceof Workspace
            && $tag instanceof Tag
            && $tag->workspace_id === $workspace->id
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
