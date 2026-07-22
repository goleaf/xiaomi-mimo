<?php

namespace App\Http\Resources;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reminder */
class ReminderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'todo_id' => $this->todo_id,
            'user_id' => $this->user_id,
            'reminded_at' => $this->reminded_at,
            'is_sent' => $this->is_sent,
            'type' => $this->type->value,
            'permissions' => [
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
