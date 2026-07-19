<?php

use App\Actions\DeleteAttachment;
use App\Actions\UploadAttachment;
use App\Models\Todo;
use App\Models\User;
use App\Providers\NativeServiceProvider;
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

test('the NativePHP v3 upgrade contract is configured', function () {
    $composer = json_decode(
        file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['require']['nativephp/mobile'])->toBe('~3.3.0')
        ->and(json_encode($composer, JSON_THROW_ON_ERROR))->not->toContain('nativephp.composer.sh')
        ->and((new NativeServiceProvider(app()))->plugins())->toBe([])
        ->and(config('nativephp.android.compile_sdk'))->toBe(36)
        ->and(config('nativephp.android.min_sdk'))->toBe(33)
        ->and(config('nativephp.android.target_sdk'))->toBe(36)
        ->and(Artisan::all())->toHaveKeys([
            'native:plugin:list',
            'native:plugin:register',
            'native:plugin:validate',
        ]);
});

test('the NativePHP Android environment contract is documented', function () {
    $environmentExample = file_get_contents(base_path('.env.example'));

    expect($environmentExample)
        ->toContain('NATIVEPHP_GRADLE_PATH=')
        ->toContain('NATIVEPHP_ANDROID_SDK_LOCATION=')
        ->toContain('NATIVEPHP_ANDROID_COMPILE_SDK=36')
        ->toContain('NATIVEPHP_ANDROID_MIN_SDK=33')
        ->toContain('NATIVEPHP_ANDROID_TARGET_SDK=36');
});

test('the NativePHP v3 mobile configuration contract is complete', function () {
    $cleanupExcludedFiles = config('nativephp.cleanup_exclude_files');
    $environmentExample = file_get_contents(base_path('.env.example'));
    $nativeConfiguration = file_get_contents(config_path('nativephp.php'));

    expect(config('nativephp.deeplink_scheme'))->toBeNull()
        ->and(config('nativephp.deeplink_host'))->toBeNull()
        ->and(config('nativephp.development_team'))->toBeNull()
        ->and(config('nativephp.permissions'))->toBe([])
        ->and(config('nativephp.permission_localizations'))->toBe([])
        ->and(config('nativephp.runtime'))->toBe([
            'mode' => 'persistent',
            'reset_instances' => true,
            'gc_between_dispatches' => false,
        ])
        ->and($cleanupExcludedFiles)->toContain(
            '.agents',
            '.github',
            '.mimocode',
            'credentials',
            'docs',
            'tests',
        )
        ->and(config('nativephp.android.compile_sdk'))
        ->toBeGreaterThanOrEqual(config('nativephp.android.target_sdk'))
        ->and(config('nativephp.android.target_sdk'))
        ->toBeGreaterThanOrEqual(config('nativephp.android.min_sdk'))
        ->and(config('nativephp.android.status_bar_style'))->toBe('auto')
        ->and(config('nativephp.android.build.minify_enabled'))->toBeFalse()
        ->and(config('nativephp.android.build.shrink_resources'))->toBeFalse()
        ->and(config('nativephp.android.build.obfuscate'))->toBeFalse()
        ->and(config('nativephp.android.build.debug_symbols'))->toBe('FULL')
        ->and(config('nativephp.android.build.parallel_builds'))->toBeTrue()
        ->and(config('nativephp.android.build.incremental_builds'))->toBeTrue()
        ->and(config('nativephp.server.http_port'))->toBe(3000)
        ->and(config('nativephp.server.ws_port'))->toBe(8081)
        ->and(config('nativephp.server.service_name'))->toBe('Xiaomi Mimo')
        ->and(config('nativephp.server.open_browser'))->toBeFalse()
        ->and(config('nativephp.server.watch_paths'))->toContain('database', 'public/build')
        ->and(config('nativephp.ipad'))->toBeFalse()
        ->and(config('nativephp.orientation.iphone'))->toBe([
            'portrait' => true,
            'upside_down' => false,
            'landscape_left' => false,
            'landscape_right' => false,
        ])
        ->and(config('nativephp.orientation.android'))->toBe([
            'portrait' => true,
            'upside_down' => false,
            'landscape_left' => false,
            'landscape_right' => false,
        ])
        ->and(config('nativephp.app_store_connect'))->toBe([
            'api_key' => null,
            'api_key_id' => null,
            'api_issuer_id' => null,
            'app_name' => null,
        ])
        ->and($nativeConfiguration)
        ->toContain("env('NATIVEPHP_ANDROID_STATUS_BAR_STYLE', 'auto')")
        ->and($environmentExample)->toContain(
            'NATIVEPHP_DEEPLINK_SCHEME=',
            'NATIVEPHP_DEEPLINK_HOST=',
            'NATIVEPHP_DEVELOPMENT_TEAM=',
            'NATIVEPHP_ANDROID_STATUS_BAR_STYLE=auto',
            'NATIVEPHP_HTTP_PORT=3000',
            'NATIVEPHP_WS_PORT=8081',
            'NATIVEPHP_SERVICE_NAME="Xiaomi Mimo"',
            'NATIVEPHP_OPEN_BROWSER=false',
            'APP_STORE_API_KEY=',
            'APP_STORE_API_KEY_ID=',
            'APP_STORE_API_ISSUER_ID=',
            'APP_STORE_APP_NAME=',
        );
});

test('the NativePHP installer values are configured', function () {
    $composer = json_decode(
        file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $environmentExample = file_get_contents(base_path('.env.example'));

    expect($composer['require'])->toHaveKey('nativephp/mobile')
        ->and(config('nativephp.app_id'))->toBe('com.goleaf.xiaomimimo')
        ->and(config('nativephp.version'))->toBe('DEBUG')
        ->and(config('nativephp.version_code'))->toBe(1)
        ->and($environmentExample)->toContain(
            'NATIVEPHP_APP_ID=com.goleaf.xiaomimimo',
            'NATIVEPHP_APP_VERSION=DEBUG',
            'NATIVEPHP_APP_VERSION_CODE=1',
        )
        ->and(Artisan::all())->toHaveKey('native:install');
});

test('the ephemeral NativePHP platform shell is ignored from the repository root', function () {
    expect(file_get_contents(base_path('.gitignore')))
        ->toContain('/nativephp');
});
