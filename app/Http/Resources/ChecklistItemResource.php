<?php

namespace App\Http\Resources;

use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChecklistItem */
class ChecklistItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checklist_id' => $this->checklist_id,
            'content' => $this->content,
            'is_checked' => $this->is_checked,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
