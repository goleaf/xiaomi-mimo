<?php

namespace App\Actions;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class DeleteAttachment
{
    public function handle(Attachment $attachment): bool
    {
        Storage::disk((string) config('filesystems.attachment_disk'))->delete($attachment->path);

        return $attachment->delete();
    }
}
