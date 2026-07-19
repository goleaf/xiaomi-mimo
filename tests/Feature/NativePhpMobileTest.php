<?php

use App\Actions\DeleteAttachment;
use App\Actions\UploadAttachment;
use App\Models\Todo;
use App\Models\User;
use App\Providers\NativeServiceProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Native\Mobile\Traits\CleansEnvFile;

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
    $appStoreConnect = config('nativephp.app_store_connect');
    $cleanupExcludedFiles = config('nativephp.cleanup_exclude_files');
    $environmentExample = file_get_contents(base_path('.env.example'));
    $nativeConfiguration = file_get_contents(config_path('nativephp.php'));

    expect(config('nativephp.deeplink_scheme'))->toBeNull()
        ->and(config('nativephp.deeplink_host'))->toBeNull()
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
        ->and($appStoreConnect)->toHaveKeys([
            'api_key',
            'api_key_path',
            'api_key_id',
            'api_issuer_id',
            'app_name',
        ])
        ->and($nativeConfiguration)
        ->toContain(
            "env('NATIVEPHP_ANDROID_STATUS_BAR_STYLE', 'auto')",
            "'api_key_path' => env('APP_STORE_API_KEY_PATH')",
        )
        ->and($environmentExample)->toContain(
            'NATIVEPHP_DEEPLINK_SCHEME=',
            'NATIVEPHP_DEEPLINK_HOST=',
            'NATIVEPHP_DEVELOPMENT_TEAM=',
            'NATIVEPHP_ANDROID_STATUS_BAR_STYLE=auto',
            'NATIVEPHP_HTTP_PORT=3000',
            'NATIVEPHP_WS_PORT=8081',
            'NATIVEPHP_SERVICE_NAME="Xiaomi Mimo"',
            'NATIVEPHP_OPEN_BROWSER=false',
            'APP_STORE_API_KEY_PATH=',
            'APP_STORE_API_KEY_ID=',
            'APP_STORE_API_ISSUER_ID=',
            'APP_STORE_APP_NAME=',
        );
});

test('the NativePHP v3 development workflow is configured', function () {
    $applicationEntryPoint = file_get_contents(resource_path('js/app.ts'));
    $environmentExample = file_get_contents(base_path('.env.example'));
    $gitIgnore = file_get_contents(base_path('.gitignore'));
    $package = json_decode(
        file_get_contents(base_path('package.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $viteConfiguration = file_get_contents(base_path('vite.config.ts'));

    expect(config('nativephp.version'))->toBe('DEBUG')
        ->and($environmentExample)->toContain('NATIVEPHP_APP_VERSION=DEBUG')
        ->and($package['dependencies'])->toHaveKey('axios')
        ->and($package['scripts']['build:ios'])->toBe('vite build --mode=ios')
        ->and($package['scripts']['build:android'])->toBe('vite build --mode=android')
        ->and($viteConfiguration)->toContain(
            'nativephpHotFile',
            'nativephpMobile',
            'hotFile: nativephpHotFile()',
            'nativephpMobile()',
        )
        ->and($applicationEntryPoint)->toContain(
            "import { axiosAdapter } from '@inertiajs/core';",
            'http: axiosAdapter()',
        )
        ->and(config('nativephp.hot_reload.watch_paths'))->toContain(
            'app',
            'routes',
            'config',
            'database',
            'public',
        )
        ->and($gitIgnore)->toContain(
            '/public/ios-hot',
            '/public/android-hot',
        )
        ->and(Artisan::all())->toHaveKeys([
            'native:open',
            'native:run',
            'native:watch',
        ]);
});

test('the NativePHP v3 deployment contract protects signing credentials', function () {
    $cleanupEnvironmentKeys = config('nativephp.cleanup_env_keys');
    $cleanupExcludedFiles = config('nativephp.cleanup_exclude_files');
    $environmentExample = file_get_contents(base_path('.env.example'));
    $gitIgnore = file_get_contents(base_path('.gitignore'));
    $packageDefinition = Artisan::all()['native:package']->getDefinition();

    expect(config('nativephp.app_id'))->toBe('com.goleaf.xiaomimimo')
        ->and(config('nativephp.version_code'))->toBeInt()
        ->and($cleanupEnvironmentKeys)->toContain(
            'ANDROID_EMULATOR',
            'ANDROID_KEYSTORE_*',
            'ANDROID_KEY_ALIAS',
            'ANDROID_KEY_PASSWORD',
            'FCM_SERVER_KEY',
            'GOOGLE_SERVICE_ACCOUNT_KEY',
            'IOS_*',
            'NATIVEPHP_ANDROID_SDK_LOCATION',
            'NATIVEPHP_DEVELOPMENT_TEAM',
            'NATIVEPHP_GRADLE_PATH',
        )
        ->and($cleanupExcludedFiles)->toContain('credentials')
        ->and($gitIgnore)->toContain('/credentials', '/nativephp')
        ->and($environmentExample)->toContain(
            'ANDROID_KEYSTORE_FILE=',
            'ANDROID_KEYSTORE_PASSWORD=',
            'ANDROID_KEY_ALIAS=',
            'ANDROID_KEY_PASSWORD=',
            'FCM_SERVER_KEY=',
            'GOOGLE_SERVICE_ACCOUNT_KEY=',
            'APP_STORE_API_KEY_PATH=',
            'APP_STORE_API_KEY_ID=',
            'APP_STORE_API_ISSUER_ID=',
            'IOS_DISTRIBUTION_CERTIFICATE_PATH=',
            'IOS_DISTRIBUTION_CERTIFICATE_PASSWORD=',
            'IOS_DISTRIBUTION_PROVISIONING_PROFILE_PATH=',
            'IOS_TEAM_ID=',
        )
        ->and(Artisan::all())->toHaveKeys([
            'native:check-build-number',
            'native:credentials',
            'native:package',
            'native:release',
        ])
        ->and($packageDefinition->getOption('build-type')->getDefault())->toBe('release')
        ->and($packageDefinition->getOption('export-method')->getDefault())->toBe('app-store')
        ->and($packageDefinition->getOption('play-store-track')->getDefault())->toBe('internal')
        ->and($packageDefinition->hasOption('no-tty'))->toBeTrue()
        ->and($packageDefinition->hasOption('upload-to-app-store'))->toBeTrue()
        ->and($packageDefinition->hasOption('upload-to-play-store'))->toBeTrue();
});

test('deployment credentials are removed from the bundled environment', function () {
    $environmentPath = tempnam(sys_get_temp_dir(), 'nativephp-env-');

    expect($environmentPath)->not->toBeFalse();

    File::put($environmentPath, implode(PHP_EOL, [
        'NATIVEPHP_APP_ID=com.goleaf.xiaomimimo',
        'NATIVEPHP_APP_VERSION=1.0.0',
        'DB_CONNECTION=sqlite',
        'ANDROID_KEYSTORE_FILE=credentials/release.jks',
        'ANDROID_KEYSTORE_PASSWORD=keystore-password',
        'ANDROID_KEY_ALIAS=xiaomi-mimo',
        'ANDROID_KEY_PASSWORD=key-password',
        'FCM_SERVER_KEY=fcm-secret',
        'GOOGLE_SERVICE_ACCOUNT_KEY=credentials/google-service.json',
        'APP_STORE_API_KEY_PATH=credentials/AuthKey.p8',
        'APP_STORE_API_KEY_ID=KEY123',
        'APP_STORE_API_ISSUER_ID=issuer-id',
        'IOS_DISTRIBUTION_CERTIFICATE_PASSWORD=certificate-password',
        'IOS_TEAM_ID=TEAM123',
    ]));

    $environmentCleaner = new class
    {
        use CleansEnvFile;

        public function clean(string $path): void
        {
            $this->cleanEnvFile($path);
        }
    };

    try {
        $environmentCleaner->clean($environmentPath);

        expect(File::get($environmentPath))
            ->toContain(
                'NATIVEPHP_APP_ID=com.goleaf.xiaomimimo',
                'NATIVEPHP_APP_VERSION=1.0.0',
                'DB_CONNECTION=sqlite',
            )
            ->not->toContain(
                'ANDROID_KEYSTORE_FILE',
                'ANDROID_KEYSTORE_PASSWORD',
                'ANDROID_KEY_ALIAS',
                'ANDROID_KEY_PASSWORD',
                'FCM_SERVER_KEY',
                'GOOGLE_SERVICE_ACCOUNT_KEY',
                'APP_STORE_API_KEY_PATH',
                'APP_STORE_API_KEY_ID',
                'APP_STORE_API_ISSUER_ID',
                'IOS_DISTRIBUTION_CERTIFICATE_PASSWORD',
                'IOS_TEAM_ID',
                'keystore-password',
                'certificate-password',
            );
    } finally {
        File::delete($environmentPath);
    }
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
