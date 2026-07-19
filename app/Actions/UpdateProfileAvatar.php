<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateProfileAvatar
{
    public function handle(User $user, UploadedFile $avatar): string
    {
        $diskName = $this->diskName();
        $path = $avatar->store('avatars/'.$user->id, $diskName);

        if (! is_string($path)) {
            throw new RuntimeException('The avatar could not be stored.');
        }

        $previousPath = $user->getRawOriginal('avatar_path');

        try {
            $user->forceFill(['avatar_path' => $path])->save();
        } catch (Throwable $exception) {
            Storage::disk($diskName)->delete($path);

            throw $exception;
        }

        if (is_string($previousPath) && $previousPath !== $path) {
            Storage::disk($diskName)->delete($previousPath);
        }

        return $path;
    }

    private function diskName(): string
    {
        $diskName = config('filesystems.attachment_disk', 'public');

        return is_string($diskName) && $diskName !== '' ? $diskName : 'public';
    }
}
