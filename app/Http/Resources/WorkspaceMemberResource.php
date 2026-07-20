<?php

namespace App\Http\Resources;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceMember */
class WorkspaceMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $member = $this->getRelation('user');
        $workspace = $this->getRelation('workspace');
        $role = $this->role;
        $canWriteThroughApi = ! $request->is('api/*')
            || ($viewer instanceof User && $viewer->tokenCan('workspaces:write'));
        $canManage = $viewer instanceof User
            && $workspace instanceof Workspace
            && $viewer->can('manageMembers', $workspace);
        $canTransfer = $viewer instanceof User
            && $workspace instanceof Workspace
            && $viewer->can('transferOwnership', $workspace);

        abort_unless($member instanceof User, 500);

        $isCurrentUser = $viewer instanceof User && $viewer->id === $member->id;

        return [
            'id' => $member->id,
            'membership_id' => $this->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $role->value,
            'is_current_user' => $isCurrentUser,
            'permissions' => [
                'update' => $canWriteThroughApi && $canManage && $role !== WorkspaceRole::Owner,
                'remove' => $canWriteThroughApi
                    && $canManage
                    && $role !== WorkspaceRole::Owner
                    && ! $isCurrentUser,
                'transfer_ownership' => $canWriteThroughApi
                    && $canTransfer
                    && $role !== WorkspaceRole::Owner,
            ],
        ];
    }
}
