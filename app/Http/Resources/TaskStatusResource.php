<?php

namespace App\Http\Resources;

use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskStatus */
class TaskStatusResource extends JsonResource
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
        $translatedName = is_string($this->translation_key) ? __($this->translation_key) : $this->name;

        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'key' => $this->key,
            'name' => is_string($translatedName) ? $translatedName : $this->name,
            'color' => $this->color,
            'position' => $this->position,
            'is_default' => $this->is_default,
            'is_completed' => $this->is_completed,
            'is_completion_target' => $this->is_completion_target,
            'is_archived' => $this->is_archived,
            'todos_count' => $this->whenCounted('todos'),
            'permissions' => [
                'update' => $canWriteThroughApi && $canManage,
                'delete' => $canWriteThroughApi && $canManage,
                'archive' => $canWriteThroughApi && $canManage && ! $this->is_default && ! $this->is_completion_target,
                'set_default' => $canWriteThroughApi && $canManage && ! $this->is_completed && ! $this->is_archived,
                'set_completion_target' => $canWriteThroughApi && $canManage && $this->is_completed && ! $this->is_archived,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
