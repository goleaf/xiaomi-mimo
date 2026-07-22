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
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'delivered_at' => $this->delivered_at,
            'failed_at' => $this->failed_at,
            'permissions' => [
                'delete' => ($request->user()?->can('delete', $this->resource) ?? false)
                    && $this->status->value !== 'processing'
                    && $this->status->value !== 'delivered',
            ],
            'created_at' => $this->created_at,
        ];
    }
}
