<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workspace */
class WorkspaceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $currentWorkspaceId = $request->hasSession()
            ? $request->session()->get('current_workspace_id')
            : null;
        $canWriteThroughApi = ! $request->is('api/*')
            || ($user instanceof User && $user->tokenCan('workspaces:write'));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members_count' => $this->whenCounted('members'),
            'projects_count' => $this->whenCounted('projects'),
            'todos_count' => $this->whenCounted('todos'),
            'is_current' => is_string($currentWorkspaceId) && $currentWorkspaceId === $this->id,
            'permissions' => [
                'view' => $user?->can('view', $this->resource) ?? false,
                'update' => $canWriteThroughApi && ($user?->can('update', $this->resource) ?? false),
                'duplicate' => $canWriteThroughApi && ($user?->can('duplicate', $this->resource) ?? false),
                'delete' => $canWriteThroughApi && ($user?->can('delete', $this->resource) ?? false),
                'manage_members' => $canWriteThroughApi && ($user?->can('manageMembers', $this->resource) ?? false),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
