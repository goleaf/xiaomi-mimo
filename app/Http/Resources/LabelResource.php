<?php

namespace App\Http\Resources;

use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Label */
class LabelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $workspace = $this->relationLoaded('workspace') ? $this->getRelation('workspace') : null;
        $canWriteThroughApi = ! $request->is('api/*')
            || ($viewer instanceof User && $viewer->tokenCan('workspaces:write'));
        $canManage = $viewer instanceof User
            && $workspace instanceof Workspace
            && $viewer->can('manageTaskConfiguration', $workspace);

        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'color' => $this->color,
            'todos_count' => $this->whenCounted('todos'),
            'permissions' => [
                'update' => $canWriteThroughApi && $canManage,
                'delete' => $canWriteThroughApi && $canManage,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
