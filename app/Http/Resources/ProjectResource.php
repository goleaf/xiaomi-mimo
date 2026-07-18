<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_archived' => $this->is_archived,
            'position' => $this->position,
            'todos_count' => $this->whenCounted('todos'),
            'completed_count' => $this->when(
                $this->relationLoaded('todos'),
                fn () => $this->todos->where('status', 'completed')->count()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
