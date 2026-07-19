<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

class UpdateProfileAvatar
{
    public function __construct(private DeleteProfileAvatar $deleteProfileAvatar) {}

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
            try {
                $this->deleteProfileAvatar->deleteStoredFile($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        if (is_string($previousPath) && $previousPath !== $path) {
            try {
                $this->deleteProfileAvatar->deleteStoredFile($previousPath);
            } catch (Throwable $exception) {
                try {
                    $user->forceFill(['avatar_path' => $previousPath])->save();
                    $this->deleteProfileAvatar->deleteStoredFile($path);
                } catch (Throwable $rollbackException) {
                    report($rollbackException);
                }

                throw $exception;
            }
        }

        return $path;
    }

    private function diskName(): string
    {
        $diskName = config('filesystems.avatar_disk', 'profile_avatars');

        return is_string($diskName) && $diskName !== '' ? $diskName : 'profile_avatars';
    }
}
