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
