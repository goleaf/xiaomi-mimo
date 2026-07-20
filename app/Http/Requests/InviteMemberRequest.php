<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('invite', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', Rule::enum(WorkspaceRole::class)->only([
                WorkspaceRole::Admin,
                WorkspaceRole::Member,
            ])],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from($this->string('role', WorkspaceRole::Member->value)->toString());
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower($this->string('email')->trim()->toString()),
            ]);
        }
    }
}
