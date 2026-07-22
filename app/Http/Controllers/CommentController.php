<?php

namespace App\Http\Controllers;

use App\Actions\CreateComment;
use App\Actions\DeleteComment;
use App\Actions\UpdateComment;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Todo;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(
        StoreCommentRequest $request,
        Todo $todo,
        CreateComment $action,
    ): RedirectResponse {
        $this->authorize('create', [Comment::class, $todo]);
        $action->handle($todo, $request->user(), $request->body);

        return back();
    }

    public function update(
        StoreCommentRequest $request,
        Comment $comment,
        UpdateComment $action,
    ): RedirectResponse {
        $this->authorize('update', $comment);
        $action->handle($comment, $request->body);

        return back();
    }

    public function destroy(
        Comment $comment,
        DeleteComment $action,
    ): RedirectResponse {
        $this->authorize('delete', $comment);
        $action->handle($comment);

        return back();
    }
}
