<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('preferences page is the canonical settings page for preferences and appearance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('preferences.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Preferences')
            ->has('preferences'));
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
