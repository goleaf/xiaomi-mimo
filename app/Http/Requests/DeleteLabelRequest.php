<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class DeleteLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');
        $label = $this->route('label');

        if (! $workspace instanceof Workspace && $label instanceof Label) {
            $workspace = $label->workspace;
        }

        return $user instanceof User
            && $workspace instanceof Workspace
            && $label instanceof Label
            && $label->workspace_id === $workspace->id
            && $user->can('manageTaskConfiguration', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
