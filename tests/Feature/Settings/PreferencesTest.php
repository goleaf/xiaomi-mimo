<?php

use App\Models\User;
use App\Models\UserPreference;
use Inertia\Testing\AssertableInertia as Assert;

test('preferences page is the canonical settings page for preferences and appearance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('preferences.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Preferences')
            ->where('preferences.language', 'en')
            ->where('preferences.timezone', 'UTC')
            ->where('preferences.date_format', 'Y-m-d')
            ->where('preferences.time_format', 'H:i')
            ->where('preferences.start_page', 'dashboard')
            ->where('timezones', fn ($timezones): bool => $timezones->contains('Europe/Vilnius')));
});

test('legacy appearance page redirects to preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertRedirectToRoute('preferences.edit');
});

test('settings preferences pages require authentication', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirectToRoute('login');
})->with([
    'preferences' => 'preferences.edit',
    'legacy appearance redirect' => 'appearance.edit',
]);

test('preferences page contains the shared appearance control', function () {
    $preferencesPage = file_get_contents(resource_path('js/pages/settings/Preferences.vue'));
    $settingsLayout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));

    expect($preferencesPage)
        ->toContain("import AppearanceTabs from '@/components/AppearanceTabs.vue'")
        ->toContain('<AppearanceTabs />')
        ->and($settingsLayout)
        ->not->toContain("label: 'Appearance'");
});

test('preferences offer every implemented default view', function () {
    $preferencesPage = file_get_contents(resource_path('js/pages/settings/Preferences.vue'));

    expect($preferencesPage)
        ->toContain('value="board"')
        ->toContain('value="list"')
        ->toContain('value="calendar"');
});

test('users can persist every bounded regional and navigation preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('preferences.edit'))
        ->put(route('preferences.update'), [
            'timezone' => 'Europe/Vilnius',
            'language' => 'lt',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'default_view' => 'board',
            'start_page' => 'tasks',
        ])
        ->assertRedirect(route('preferences.edit'))
        ->assertSessionHas('locale', 'lt');

    $preferences = $user->preferences()->firstOrFail();

    expect($preferences->timezone)->toBe('Europe/Vilnius')
        ->and($preferences->language)->toBe('lt')
        ->and($preferences->date_format)->toBe('d.m.Y')
        ->and($preferences->time_format)->toBe('H:i')
        ->and($preferences->default_view)->toBe('board')
        ->and($preferences->start_page)->toBe('tasks');
});

test('preference values are allowlisted and validation follows the saved locale', function () {
    $user = User::factory()->create();
    UserPreference::factory()->for($user)->create(['language' => 'ru']);

    $response = $this->actingAs($user)
        ->from(route('preferences.edit'))
        ->put(route('preferences.update'), [
            'timezone' => 'Not/A-Timezone',
            'language' => 'fr',
            'date_format' => 'arbitrary',
            'time_format' => 'arbitrary',
            'default_view' => 'arbitrary',
            'start_page' => 'https://example.com',
        ])
        ->assertRedirect(route('preferences.edit'))
        ->assertSessionHasErrors([
            'timezone',
            'language',
            'date_format',
            'time_format',
            'default_view',
            'start_page',
        ]);

    expect($response->getSession()->get('errors')->get('timezone')[0])
        ->toContain('часовой пояс');

    expect($user->preferences()->firstOrFail()->language)->toBe('ru');
});
