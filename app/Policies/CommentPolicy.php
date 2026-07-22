<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        return $comment->todo->workspace->hasMember($user);
    }

    public function create(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $user->can('update', $comment->todo->workspace);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $user->can('update', $comment->todo->workspace);
    }
}
