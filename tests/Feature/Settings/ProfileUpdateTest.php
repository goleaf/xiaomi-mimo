<?php

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->where('user.id', $user->id)
            ->where('user.avatar_url', null)
            ->where('canVerifyEmail', true)
            ->where('labels.navigation_label', 'Settings')
            ->where('labels.avatar.title', 'Profile photo')
            ->where('labels.personal.title', 'Personal information')
            ->where('labels.delete.title', 'Delete account'));
});

test('profile page uses the authenticated user locale with English fallback', function (
    string $language,
    string $title,
    string $avatarTitle,
    string $deleteLabel,
) {
    $user = User::factory()->create();
    $user->preferences()->create(['language' => $language]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('labels.title', $title)
            ->where('labels.avatar.title', $avatarTitle)
            ->where('labels.delete.confirm', $deleteLabel));
})->with([
    'Lithuanian' => ['lt', 'Profilis', 'Profilio nuotrauka', 'Ištrinti paskyrą'],
    'Russian' => ['ru', 'Профиль', 'Фото профиля', 'Удалить аккаунт'],
    'unsupported locale falls back to English' => ['de', 'Profile', 'Profile photo', 'Delete account'],
]);

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can upload and view their avatar', function () {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800)->size(100);

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), ['avatar' => $avatar])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'profile-avatar-updated')
        ->assertRedirect(route('profile.edit'));

    $avatarPath = $user->refresh()->getRawOriginal('avatar_path');

    expect($avatarPath)->toBeString();
    Storage::disk('profile_avatars')->assertExists($avatarPath);

    $this->actingAs($user)
        ->get(route('profile.avatar.show'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('avatar routes require authentication', function () {
    $this->get(route('profile.avatar.show'))->assertRedirect(route('login'));
    $this->post(route('profile.avatar.update'))->assertRedirect(route('login'));
    $this->delete(route('profile.avatar.destroy'))->assertRedirect(route('login'));
});

test('one user cannot view another user avatar', function () {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $avatarOwner = User::factory()->create();
    $avatarPath = 'avatars/'.$avatarOwner->id.'/avatar.jpg';
    Storage::disk('profile_avatars')->put($avatarPath, 'avatar');
    $avatarOwner->forceFill(['avatar_path' => $avatarPath])->save();

    $this->actingAs(User::factory()->create())
        ->get(route('profile.avatar.show'))
        ->assertNotFound();
});

test('uploading a new avatar removes the previous file', function () {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $previousPath = 'avatars/'.$user->id.'/previous.jpg';
    Storage::disk('profile_avatars')->put($previousPath, 'previous-avatar');
    $user->forceFill(['avatar_path' => $previousPath])->save();

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('replacement.webp', 600, 600)->size(100),
        ])
        ->assertSessionHasNoErrors();

    $avatarPath = $user->refresh()->getRawOriginal('avatar_path');

    expect($avatarPath)->toBeString()->not->toBe($previousPath);
    Storage::disk('profile_avatars')->assertMissing($previousPath);
    Storage::disk('profile_avatars')->assertExists($avatarPath);
});

test('user can remove their avatar', function () {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $avatarPath = 'avatars/'.$user->id.'/avatar.png';
    Storage::disk('profile_avatars')->put($avatarPath, 'avatar');
    $user->forceFill(['avatar_path' => $avatarPath])->save();

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'profile-avatar-removed')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->getRawOriginal('avatar_path'))->toBeNull();
    Storage::disk('profile_avatars')->assertMissing($avatarPath);
});

test('avatar removal failure restores the stored avatar path', function () {
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $avatarPath = 'avatars/'.$user->id.'/avatar.png';
    $user->forceFill(['avatar_path' => $avatarPath])->save();

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')->once()->with($avatarPath)->andReturnFalse();
    Storage::shouldReceive('disk')->once()->with('profile_avatars')->andReturn($disk);
    Exceptions::fake();

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertServerError();

    expect($user->refresh()->getRawOriginal('avatar_path'))->toBe($avatarPath);
    Exceptions::assertReported(RuntimeException::class);
});

test('avatar upload validates image type size and dimensions', function (Closure $avatar): void {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar.update'), ['avatar' => $avatar()])
        ->assertSessionHasErrors('avatar')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->getRawOriginal('avatar_path'))->toBeNull();
})->with([
    'unsupported SVG' => fn () => UploadedFile::fake()->create('avatar.svg', 100, 'image/svg+xml'),
    'image with an unsupported extension' => fn () => UploadedFile::fake()->image('avatar.txt')->size(100),
    'file larger than two megabytes' => fn () => UploadedFile::fake()->image('avatar.jpg')->size(2049),
    'image wider than 4096 pixels' => fn () => UploadedFile::fake()->image('avatar.png', 4097, 500)->size(100),
]);

test('user can delete their account', function () {
    Storage::fake('profile_avatars');
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $avatarPath = 'avatars/'.$user->id.'/avatar.jpg';
    Storage::disk('profile_avatars')->put($avatarPath, 'avatar');
    $user->forceFill(['avatar_path' => $avatarPath])->save();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    Storage::disk('profile_avatars')->assertMissing($avatarPath);
});

test('account deletion reports a staged avatar cleanup failure', function () {
    config(['filesystems.avatar_disk' => 'profile_avatars']);

    $user = User::factory()->create();
    $avatarPath = 'avatars/'.$user->id.'/avatar.jpg';
    $user->forceFill(['avatar_path' => $avatarPath])->save();

    $disk = Mockery::mock(Filesystem::class);
    $stagedPath = null;
    $disk->shouldReceive('move')->once()->withArgs(function (string $source, string $destination) use ($avatarPath, &$stagedPath): bool {
        $stagedPath = $destination;

        return $source === $avatarPath;
    })->andReturnTrue();
    $disk->shouldReceive('delete')->once()->with(Mockery::type('string'))->andReturnFalse();
    Storage::shouldReceive('disk')->twice()->with('profile_avatars')->andReturn($disk);
    Exceptions::fake();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    expect($stagedPath)->toBeString()->toStartWith('pending-deletions/');
    Exceptions::assertReported(RuntimeException::class);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
