<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'workspace_id' => $this->workspace_id,
            'assigned_to' => $this->assigned_to,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->due_date?->toDateString(),
            'start_date' => $this->start_date?->toDateString(),
            'estimated_time' => $this->estimated_time,
            'spent_time' => $this->spent_time,
            'is_pinned' => $this->is_pinned,
            'is_favorite' => $this->is_favorite,
            'is_archived' => $this->is_archived,
            'is_recurring' => $this->is_recurring,
            'recurring_rule' => $this->recurring_rule,
            'position' => $this->position,
            'completed_at' => $this->completed_at,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments_count' => $this->whenCounted('comments'),
            'checklists_count' => $this->whenCounted('checklists'),
            'attachments_count' => $this->whenCounted('attachments'),
            'subtasks_count' => $this->whenCounted('subtasks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
