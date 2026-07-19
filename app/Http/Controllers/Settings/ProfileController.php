<?php

namespace App\Http\Controllers\Settings;

use App\Actions\DeleteProfileAvatar;
use App\Actions\UpdateProfileAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileAvatarDeleteRequest;
use App\Http\Requests\Settings\ProfileAvatarUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        return Inertia::render('settings/Profile', [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'email_verified_at']),
                'avatar_url' => is_string($user->getAttribute('avatar_path'))
                    ? route('profile.avatar.show', ['v' => $user->updated_at?->getTimestamp()])
                    : null,
            ],
            'canVerifyEmail' => Features::enabled(Features::emailVerification()),
            'status' => $request->session()->get('status'),
            'labels' => $this->labels(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $this->authenticatedUser($request);

        if ($validated['email'] !== $user->email) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        $user->update($validated);

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateAvatar(
        ProfileAvatarUpdateRequest $request,
        UpdateProfileAvatar $updateProfileAvatar,
    ): RedirectResponse {
        $avatar = $request->file('avatar');

        if (! $avatar instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'avatar' => __('validation.uploaded', ['attribute' => 'avatar']),
            ]);
        }

        $updateProfileAvatar->handle($this->authenticatedUser($request), $avatar);

        return redirect()->route('profile.edit')->with('status', 'profile-avatar-updated');
    }

    public function destroyAvatar(
        ProfileAvatarDeleteRequest $request,
        DeleteProfileAvatar $deleteProfileAvatar,
    ): RedirectResponse {
        $deleteProfileAvatar->handle($this->authenticatedUser($request));

        return redirect()->route('profile.edit')->with('status', 'profile-avatar-removed');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $path = $this->authenticatedUser($request)->getRawOriginal('avatar_path');

        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk($this->avatarDiskName());
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(
        ProfileDeleteRequest $request,
        DeleteProfileAvatar $deleteProfileAvatar,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        $avatarPath = $user->getRawOriginal('avatar_path');
        Auth::logout();
        $stagedAvatarPath = is_string($avatarPath) && $avatarPath !== ''
            ? $deleteProfileAvatar->stageStoredFile($avatarPath)
            : null;

        try {
            if (! $user->delete()) {
                throw new RuntimeException('The user account could not be deleted.');
            }
        } catch (Throwable $exception) {
            if (is_string($stagedAvatarPath)) {
                try {
                    $deleteProfileAvatar->restoreStagedFile($stagedAvatarPath, $avatarPath);
                } catch (Throwable $rollbackException) {
                    report($rollbackException);
                }
            }

            throw $exception;
        }

        if (is_string($stagedAvatarPath)) {
            try {
                $deleteProfileAvatar->deleteStoredFile($stagedAvatarPath);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function avatarDiskName(): string
    {
        $diskName = config('filesystems.avatar_disk', 'profile_avatars');

        return is_string($diskName) && $diskName !== '' ? $diskName : 'profile_avatars';
    }

    /** @return array<string, mixed> */
    private function labels(): array
    {
        $labels = trans('settings.profile');

        return is_array($labels) ? $labels : [];
    }
}
