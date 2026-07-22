<?php

namespace App\Actions;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAttachment
{
    public function handle(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk((string) config('filesystems.attachment_disk'));

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->filename, [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
