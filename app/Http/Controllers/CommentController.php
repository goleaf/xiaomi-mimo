<?php

namespace App\Http\Controllers;

use App\Actions\CreateComment;
use App\Actions\DeleteComment;
use App\Actions\UpdateComment;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(
        StoreCommentRequest $request,
        Todo $todo,
        CreateComment $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('create', [Comment::class, $todo]);
        $comment = $action->handle($todo, $request->user(), $request->body);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['comment' => new CommentResource($comment)], 201);
    }

    public function update(
        StoreCommentRequest $request,
        Comment $comment,
        UpdateComment $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $comment);
        $comment = $action->handle($comment, $request->body);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['comment' => new CommentResource($comment)]);
    }

    public function destroy(
        Request $request,
        Comment $comment,
        DeleteComment $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('delete', $comment);
        $action->handle($comment);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }
}
