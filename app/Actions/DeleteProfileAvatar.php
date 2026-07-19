<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DeleteProfileAvatar
{
    public function handle(User $user): void
    {
        $path = $user->getRawOriginal('avatar_path');

        if (! is_string($path) || $path === '') {
            return;
        }

        $user->forceFill(['avatar_path' => null])->save();

        try {
            $this->deleteStoredFile($path);
        } catch (Throwable $exception) {
            try {
                $user->forceFill(['avatar_path' => $path])->save();
            } catch (Throwable $rollbackException) {
                report($rollbackException);
            }

            throw $exception;
        }
    }

    public function deleteStoredFile(string $path): void
    {
        if (! Storage::disk($this->diskName())->delete($path)) {
            throw new RuntimeException('The profile avatar could not be deleted.');
        }
    }

    private function diskName(): string
    {
        $diskName = config('filesystems.avatar_disk', 'profile_avatars');

        return is_string($diskName) && $diskName !== '' ? $diskName : 'profile_avatars';
    }
}
