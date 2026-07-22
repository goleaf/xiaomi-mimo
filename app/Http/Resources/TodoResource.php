<?php

namespace App\Http\Resources;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Todo */
class TodoResource extends JsonResource
{
    /** @return array<string, mixed> */
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
            'status' => $this->statusKey(),
            'status_id' => $this->status_id,
            'priority' => $this->priorityKey(),
            'priority_id' => $this->priority_id,
            'status_definition' => new TaskStatusResource($this->whenLoaded('statusDefinition')),
            'priority_definition' => new TaskPriorityResource($this->whenLoaded('priorityDefinition')),
            'is_completed' => $this->relationLoaded('statusDefinition')
                ? (bool) $this->statusDefinition?->is_completed
                : $this->completed_at !== null,
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
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'checklists' => ChecklistResource::collection($this->whenLoaded('checklists')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),
            'subtasks' => TodoResource::collection($this->whenLoaded('subtasks')),
            'comments_count' => $this->whenCounted('comments'),
            'checklists_count' => $this->whenCounted('checklists'),
            'attachments_count' => $this->whenCounted('attachments'),
            'subtasks_count' => $this->whenCounted('subtasks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
