<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('manageMembers', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WorkspaceRole::class)->only([
                WorkspaceRole::Admin,
                WorkspaceRole::Member,
            ])],
        ];
    }

    public function membership(): WorkspaceMember
    {
        $workspace = $this->route('workspace');
        $userId = $this->route('userId');

        abort_unless($workspace instanceof Workspace && is_string($userId), 404);

        return $workspace->memberships()
            ->with(['user:id,name,email', 'workspace'])
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from($this->string('role')->toString());
    }
}
