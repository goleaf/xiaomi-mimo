<?php

use Illuminate\Support\Facades\File;

test('primary workspace pages use the shared warm precision header', function (string $page) {
    expect(File::get(resource_path("js/pages/{$page}")))
        ->toContain('WorkspacePageHeader')
        ->toContain('bg-muted/20')
        ->toContain('max-w-[1480px]');
})->with([
    'dashboard' => 'Dashboard.vue',
    'tasks' => 'tasks/Index.vue',
    'task detail' => 'tasks/Show.vue',
    'project detail' => 'projects/Show.vue',
    'workspaces' => 'workspaces/Index.vue',
]);

test('shared shells carry the projects page visual language', function () {
    expect(File::get(resource_path('js/layouts/auth/AuthSimpleLayout.vue')))
        ->toContain('bg-muted/20')
        ->toContain('rounded-[1.75rem]')
        ->and(File::get(resource_path('js/layouts/settings/Layout.vue')))
        ->toContain('bg-muted/20')
        ->toContain('rounded-[1.5rem]')
        ->and(File::get(resource_path('js/components/ui/card/Card.vue')))
        ->toContain('rounded-[1.5rem]')
        ->toContain('border-border/80');
});
