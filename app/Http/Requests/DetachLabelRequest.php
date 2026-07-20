<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class DetachLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $todo = $this->route('todo');
        $label = $this->route('label');

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || ! $todo instanceof Todo
            || ! $label instanceof Label) {
            return false;
        }

        $todo->setRelation('workspace', $workspace);

        return $todo->workspace_id === $workspace->id
            && $label->workspace_id === $workspace->id
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
