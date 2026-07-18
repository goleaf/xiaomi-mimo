<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'subject_type' => class_basename($this->subject_type),
            'subject_id' => $this->subject_id,
            'event' => $this->event,
            'properties' => $this->properties,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
