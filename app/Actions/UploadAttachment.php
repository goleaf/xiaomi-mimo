<?php

namespace App\Actions;

use App\Models\Attachment;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadAttachment
{
    public function handle(Todo $todo, User $user, UploadedFile $file): Attachment
    {
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs(
            'attachments',
            $filename,
            (string) config('filesystems.attachment_disk'),
        );

        return Attachment::query()->create([
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
