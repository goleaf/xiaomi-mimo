<?php

namespace App\Actions;

use App\Models\Comment;

class UpdateComment
{
    public function handle(Comment $comment, string $body): Comment
    {
        $comment->update(['body' => $body]);

        return $comment->fresh('user');
    }
}
