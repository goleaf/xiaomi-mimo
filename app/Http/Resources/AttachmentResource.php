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
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'url' => $this->url,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
