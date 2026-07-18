<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $attachment->todo->workspace->hasMember($user);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $attachment->user_id === $user->id || $attachment->todo->workspace->isOwner($user);
    }
}
