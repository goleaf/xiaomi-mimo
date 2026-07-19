<?php

namespace App\Http\Resources;

use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Checklist */
class ChecklistResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'todo_id' => $this->todo_id,
            'name' => $this->name,
            'position' => $this->position,
            'items' => ChecklistItemResource::collection($this->whenLoaded('items')),
            'progress' => $this->whenLoaded('items', fn () => $this->items->where('is_checked')->count() / max($this->items->count(), 1) * 100),
            'created_at' => $this->created_at,
        ];
    }
}
