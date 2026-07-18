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

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Todo $todo, CreateComment $action): JsonResponse
    {
        $this->authorize('create', [Comment::class, $todo]);
        $comment = $action->handle($todo, $request->user(), $request->body);

        return response()->json(['comment' => new CommentResource($comment)], 201);
    }

    public function update(StoreCommentRequest $request, Comment $comment, UpdateComment $action): JsonResponse
    {
        $this->authorize('update', $comment);
        $comment = $action->handle($comment, $request->body);

        return response()->json(['comment' => new CommentResource($comment)]);
    }

    public function destroy(Comment $comment, DeleteComment $action): JsonResponse
    {
        $this->authorize('delete', $comment);
        $action->handle($comment);

        return response()->json(null, 204);
    }
}
