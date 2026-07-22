<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attachment */
class AttachmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'todo_id' => $this->todo_id,
            'user_id' => $this->user_id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => $request->routeIs('api.v1.*')
                ? route('api.v1.attachments.download', [$this->todo_id, $this->id], false)
                : ($request->routeIs('api.legacy.*')
                    ? route('api.legacy.attachments.download', $this->id, false)
                    : route('attachments.download', $this->id, false)),
            'user' => new UserResource($this->whenLoaded('user')),
            'permissions' => [
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
