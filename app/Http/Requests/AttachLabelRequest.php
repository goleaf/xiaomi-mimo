<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachLabelRequest extends FormRequest
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
            'label_id' => [
                'required',
                'uuid',
                Rule::exists(Label::class, 'id')->where(
                    'workspace_id',
                    $workspace instanceof Workspace ? $workspace->id : '',
                ),
            ],
        ];
    }

    public function labelId(): string
    {
        return $this->string('label_id')->toString();
    }
}
