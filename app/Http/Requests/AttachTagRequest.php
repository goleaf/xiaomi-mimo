<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $todo = $this->route('todo');

        if (! $user instanceof User || ! $workspace instanceof Workspace || ! $todo instanceof Todo) {
            return false;
        }

        $todo->setRelation('workspace', $workspace);

        return $todo->workspace_id === $workspace->id && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'tag_id' => [
                'required',
                'uuid',
                Rule::exists(Tag::class, 'id')->where(
                    'workspace_id',
                    $workspace instanceof Workspace ? $workspace->id : '',
                ),
            ],
        ];
    }

    public function tagId(): string
    {
        return $this->string('tag_id')->toString();
    }
}
