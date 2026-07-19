<?php

use App\Actions\DeleteAttachment;
use App\Actions\UploadAttachment;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

test('the application is configured for the NativePHP on-device runtime', function () {
    $cleanupEnvironmentKeys = config('nativephp.cleanup_env_keys');

    expect(config('nativephp.app_id'))->toBe('com.goleaf.xiaomimimo')
        ->and(config('nativephp.start_url'))->toBe('/')
        ->and(config('nativephp.runtime.mode'))->toBe('persistent')
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('filesystems.attachment_disk'))->toBe('public')
        ->and($cleanupEnvironmentKeys)->toContain(
            'APP_KEY',
            'APP_STORE_*',
            'AWS_*',
            'DB_PASSWORD',
            'MAIL_PASSWORD',
            '*_SECRET',
        )
        ->and(Artisan::all())->toHaveKeys([
            'native:install',
            'native:jump',
            'native:run',
        ]);
});

test('attachments use the configured on-device filesystem', function () {
    Storage::fake('mobile_public');
    config()->set('filesystems.attachment_disk', 'mobile_public');

    $todo = Todo::factory()->create();
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('project-notes.txt', 12, 'text/plain');

    $attachment = app(UploadAttachment::class)->handle($todo, $user, $file);

    Storage::disk('mobile_public')->assertExists($attachment->path);
    expect($attachment->url)->toBe(Storage::disk('mobile_public')->url($attachment->path));

    expect(app(DeleteAttachment::class)->handle($attachment))->toBeTrue();
    Storage::disk('mobile_public')->assertMissing($attachment->path);
});
