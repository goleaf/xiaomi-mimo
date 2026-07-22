<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

test('frontend translations cover every supported locale', function () {
    $catalogs = collect(['en', 'lt', 'ru'])
        ->mapWithKeys(fn (string $locale): array => [
            $locale => collect(Arr::dot(trans('ui', locale: $locale))),
        ]);

    $englishKeys = $catalogs->get('en')->keys()->all();

    foreach ($catalogs as $locale => $catalog) {
        expect($catalog->keys()->all())->toBe($englishKeys)
            ->and($catalog->filter(fn (mixed $value): bool => ! is_string($value) || blank($value)))
            ->toBeEmpty("The {$locale} frontend catalog contains an empty translation.");
    }
});

test('every application translation catalog has locale parity', function () {
    $catalogs = collect(File::files(lang_path('en')))
        ->map(fn (SplFileInfo $file): string => $file->getBasename('.php'))
        ->reject(fn (string $catalog): bool => in_array($catalog, ['auth', 'passwords', 'validation'], true));

    foreach ($catalogs as $catalog) {
        $englishKeys = collect(Arr::dot(trans($catalog, locale: 'en')))->keys()->all();

        foreach (['lt', 'ru'] as $locale) {
            expect(collect(Arr::dot(trans($catalog, locale: $locale)))->keys()->all())
                ->toBe($englishKeys, "The {$locale}/{$catalog}.php catalog is out of sync.");
        }
    }
});

test('guest requests use a supported browser language and English fallback', function () {
    $this->withHeader('Accept-Language', 'lt-LT,lt;q=0.9')
        ->get(route('login'))
        ->assertOk()
        ->assertSee('<html lang="lt" dir="ltr"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('ui.auth.login.title', 'Prisijungimas'));

    $this->withHeader('Accept-Language', 'fr-FR')
        ->get(route('login'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('ui.auth.login.title', 'Log in'));
});

test('localized framework messages fall back to English for uncovered rules', function () {
    expect(trans('validation.timezone', locale: 'ru'))
        ->toContain('часовой пояс')
        ->and(trans('validation.after_or_equal', locale: 'ru'))
        ->toBe('The :attribute field must be a date after or equal to :date.')
        ->and(trans('auth.failed', locale: 'lt'))
        ->toContain('prisijungimo duomenys');
});

test('frontend formatters and document locale synchronization are centralized', function () {
    $ui = File::get(resource_path('js/composables/useUi.ts'));
    $workspaceUi = File::get(resource_path('js/composables/useWorkspaceUi.ts'));
    $application = File::get(resource_path('js/app.ts'));

    expect($ui)
        ->toContain("from '@/lib/formatters'")
        ->not->toContain('new Intl.DateTimeFormat')
        ->and($workspaceUi)
        ->toContain("from '@/lib/formatters'")
        ->not->toContain('new Intl.DateTimeFormat')
        ->and($application)
        ->toContain("router.on('success'")
        ->toContain('document.documentElement.lang = language')
        ->toContain("document.documentElement.dir = 'ltr'");
});

test('shared frontend copy follows the supported user locale with English fallback', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    UserPreference::create([
        'user_id' => $user->id,
        'timezone' => 'Europe/Vilnius',
        'language' => 'ru',
    ]);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ui.tasks.index.title', 'Задачи')
            ->where('ui.tasks.detail.checklists', 'Списки')
            ->where('ui.settings.navigation.security', 'Безопасность'));

    $user->preferences()->update(['language' => 'en']);
    $user->unsetRelation('preferences');

    $this->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ui.tasks.index.title', 'Tasks')
            ->where('ui.common.actions.cancel', 'Cancel'));
});

test('frontend application code uses generated Wayfinder routes', function () {
    $frontendSource = collect(File::allFiles(resource_path('js')))
        ->reject(fn (SplFileInfo $file): bool => str_contains($file->getPathname(), '/routes/')
            || str_contains($file->getPathname(), '/actions/')
            || str_contains($file->getPathname(), '/wayfinder/')
            || str_contains($file->getPathname(), '/components/ui/'))
        ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['ts', 'vue'], true))
        ->map(fn (SplFileInfo $file): string => $file->getContents())
        ->implode("\n");

    expect($frontendSource)
        ->not->toMatch('/\broute\s*\(/')
        ->not->toMatch('/[\'\"]\/settings(?:\/|[\'\"])/');

    expect(File::exists(resource_path('js/lib/route.ts')))->toBeFalse();
});
