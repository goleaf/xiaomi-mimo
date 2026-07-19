<?php

namespace App\Http\Controllers;

use App\Actions\DeleteAttachment;
use App\Actions\UploadAttachment;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Todo $todo,
        UploadAttachment $action,
    ): JsonResponse {
        $attachment = $action->handle($todo, $request->user(), $request->uploadedFile());

        return response()->json(['attachment' => new AttachmentResource($attachment)], 201);
    }

    public function destroy(Attachment $attachment, DeleteAttachment $action): JsonResponse
    {
        $this->authorize('delete', $attachment);
        $action->handle($attachment);

        return response()->json(null, 204);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk((string) config('filesystems.attachment_disk'))
            ->download($attachment->path, $attachment->filename);
    }
}
