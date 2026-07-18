<?php

namespace App\Actions;

use App\Models\Comment;

class DeleteComment
{
    public function handle(Comment $comment): bool
    {
        return $comment->delete();
    }
}
