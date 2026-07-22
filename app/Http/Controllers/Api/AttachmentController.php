<?php

namespace App\Http\Controllers\Api;

use App\Actions\DeleteAttachment;
use App\Actions\UploadAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function index(Todo $todo): AnonymousResourceCollection
    {
        $this->authorize('view', $todo);

        return AttachmentResource::collection($todo->attachments()->with('user')->get());
    }

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

    public function destroyScoped(Todo $todo, Attachment $attachment, DeleteAttachment $action): JsonResponse
    {
        return $this->destroy($attachment, $action);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk((string) config('filesystems.attachment_disk'))
            ->download($attachment->path, $attachment->filename);
    }

    public function downloadScoped(Todo $todo, Attachment $attachment): StreamedResponse
    {
        return $this->download($attachment);
    }
}
