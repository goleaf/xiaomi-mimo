<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateComment;
use App\Actions\DeleteComment;
use App\Actions\UpdateComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommentIndexRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(CommentIndexRequest $request, Todo $todo): AnonymousResourceCollection
    {
        return CommentResource::collection(
            $todo->comments()
                ->with(['user', 'todo.workspace'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->cursorPaginate($request->perPage())
                ->withQueryString(),
        );
    }

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

    public function updateScoped(
        StoreCommentRequest $request,
        Todo $todo,
        Comment $comment,
        UpdateComment $action,
    ): JsonResponse {
        return $this->update($request, $comment, $action);
    }

    public function destroy(Comment $comment, DeleteComment $action): JsonResponse
    {
        $this->authorize('delete', $comment);
        $action->handle($comment);

        return response()->json(null, 204);
    }

    public function destroyScoped(Todo $todo, Comment $comment, DeleteComment $action): JsonResponse
    {
        return $this->destroy($comment, $action);
    }
}
