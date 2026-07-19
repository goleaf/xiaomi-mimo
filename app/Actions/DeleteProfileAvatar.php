<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DeleteProfileAvatar
{
    public function handle(User $user): void
    {
        $path = $user->getRawOriginal('avatar_path');

        if (! is_string($path) || $path === '') {
            return;
        }

        $user->forceFill(['avatar_path' => null])->save();
        Storage::disk($this->diskName())->delete($path);
    }

    private function diskName(): string
    {
        $diskName = config('filesystems.attachment_disk', 'public');

        return is_string($diskName) && $diskName !== '' ? $diskName : 'public';
    }
}
