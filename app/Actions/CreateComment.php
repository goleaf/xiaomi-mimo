<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;

class CreateComment
{
    public function handle(Todo $todo, User $user, string $body): Comment
    {
        return $todo->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ])->load('user');
    }
}
