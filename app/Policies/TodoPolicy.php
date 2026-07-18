<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;

class TodoPolicy
{
    public function view(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function create(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function update(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function delete(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function complete(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }
}
