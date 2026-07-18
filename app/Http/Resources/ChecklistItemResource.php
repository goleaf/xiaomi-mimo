<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemResource extends JsonResource
{
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
