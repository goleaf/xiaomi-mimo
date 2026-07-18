<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'todo_id' => $this->todo_id,
            'user_id' => $this->user_id,
            'reminded_at' => $this->reminded_at,
            'is_sent' => $this->is_sent,
            'type' => $this->type->value,
            'created_at' => $this->created_at,
        ];
    }
}
