<?php

use Illuminate\Support\Facades\File;

test('primary workspace pages use the shared warm precision header', function (string $page) {
    expect(File::get(resource_path("js/pages/{$page}")))
        ->toContain('WorkspacePageHeader')
        ->toContain('bg-muted/20')
        ->toContain('max-w-[1480px]');
})->with([
    'dashboard' => 'Dashboard.vue',
    'activity' => 'activity/Index.vue',
    'calendar' => 'calendar/Index.vue',
    'notifications' => 'notifications/Index.vue',
    'projects' => 'projects/Index.vue',
    'tasks' => 'tasks/Index.vue',
    'task detail' => 'tasks/Show.vue',
    'project detail' => 'projects/Show.vue',
    'workspaces' => 'workspaces/Index.vue',
]);

test('every settings page configures the shared projects style header', function (string $page) {
    expect(File::get(resource_path("js/pages/settings/{$page}.vue")))
        ->toContain('setLayoutProps<')
        ->toContain('settingsTitle:')
        ->toContain('settingsDescription:');
})->with([
    'backup' => 'Backup',
    'export' => 'Export',
    'members' => 'Members',
    'notifications' => 'Notifications',
    'preferences' => 'Preferences',
    'profile' => 'Profile',
    'security' => 'Security',
]);

test('shared shells carry the projects page visual language', function () {
    expect(File::get(resource_path('js/layouts/auth/AuthSimpleLayout.vue')))
        ->toContain('bg-muted/20')
        ->toContain('rounded-[1.75rem]')
        ->and(File::get(resource_path('js/layouts/settings/Layout.vue')))
        ->toContain('bg-muted/20')
        ->toContain('rounded-[1.5rem]')
        ->toContain('WorkspacePageHeader')
        ->toContain('max-w-[1480px]')
        ->and(File::get(resource_path('js/components/ui/card/Card.vue')))
        ->toContain('rounded-[1.5rem]')
        ->toContain('border-border/80');
});

test('dormant layouts delegate to the canonical projects style shells', function () {
    expect(File::get(resource_path('js/layouts/auth/AuthCardLayout.vue')))
        ->toContain('AuthSimpleLayout')
        ->not->toContain('bg-muted p-6')
        ->and(File::get(resource_path('js/layouts/auth/AuthSplitLayout.vue')))
        ->toContain('AuthSimpleLayout')
        ->not->toContain('bg-zinc-900')
        ->and(File::get(resource_path('js/layouts/app/AppHeaderLayout.vue')))
        ->toContain('AppSidebarLayout')
        ->not->toContain('variant="header"');
});

test('the shared state surface supports accessible loading and error variants', function () {
    expect(File::get(resource_path('js/components/shared/EmptyState.vue')))
        ->toContain("type EmptyStateStatus = 'empty' | 'loading' | 'error'")
        ->toContain('aria-busy')
        ->toContain(':role=')
        ->toContain("? 'alert'")
        ->toContain('<Skeleton')
        ->toContain('<LoaderCircle')
        ->toContain('<AlertTriangle');
});

test('autosave uses lifecycle safe Vue watcher cleanup', function () {
    expect(File::get(resource_path('js/composables/useAutosave.ts')))
        ->toContain('onCleanup')
        ->toContain('clearTimeout(timeoutId)')
        ->not->toContain("from '@vueuse/core'")
        ->not->toContain('debouncedSave.cancel');
});

test('workspace dialogs preserve the projects visual contract on every viewport', function () {
    expect(File::get(resource_path('js/components/shared/WorkspaceDialogContent.vue')))
        ->toContain('rounded-[1.75rem]')
        ->toContain('max-h-[calc(100svh-1.5rem)]')
        ->toContain('overflow-y-auto')
        ->toContain('inset-y-0 left-0 w-1.5')
        ->toContain('border-orange-500/20')
        ->and(File::get(resource_path('js/components/shared/WorkspaceConfirmDialog.vue')))
        ->toContain('WorkspaceDialogContent')
        ->toContain("accent=\"destructive ? 'red' : 'orange'\"");
});

test('feature dialogs use the shared projects style dialog surface', function (string $file) {
    expect(File::get(resource_path("js/{$file}")))
        ->toContain('WorkspaceDialogContent')
        ->not->toContain('<DialogContent');
})->with([
    'project create' => 'components/project/ProjectCreateDialog.vue',
    'task create' => 'components/task/TaskCreateDialog.vue',
    'workspace create' => 'pages/workspaces/Index.vue',
    'delete account' => 'components/DeleteUser.vue',
    'remove passkey' => 'components/PasskeyItem.vue',
    'two factor setup' => 'components/TwoFactorSetupModal.vue',
    'remove member' => 'pages/settings/Members.vue',
]);

test('destructive actions use application confirmations instead of browser dialogs', function (string $file) {
    expect(File::get(resource_path("js/{$file}")))
        ->toContain('WorkspaceConfirmDialog')
        ->not->toContain('confirm(');
})->with([
    'task list' => 'pages/tasks/Index.vue',
    'project task list' => 'pages/projects/Show.vue',
    'task drawer' => 'components/task/TaskDetail.vue',
    'backup restore' => 'pages/settings/Backup.vue',
    'two factor disable' => 'pages/settings/Security.vue',
]);

test('task interfaces use accessible application controls', function () {
    expect(File::get(resource_path('js/components/task/TaskDetail.vue')))
        ->toContain('<Sheet')
        ->toContain('<Checkbox')
        ->not->toContain('<Teleport')
        ->not->toContain('type="checkbox"')
        ->and(File::get(resource_path('js/components/task/TaskCreateDialog.vue')))
        ->toContain('<Checkbox')
        ->not->toContain('type="checkbox"')
        ->and(File::get(resource_path('js/pages/tasks/Index.vue')))
        ->toContain('<Checkbox')
        ->not->toContain('type="checkbox"')
        ->and(File::get(resource_path('js/pages/projects/Show.vue')))
        ->toContain('<Checkbox')
        ->not->toContain('type="checkbox"');
});

test('task rows preserve whole-row selection through a keyboard focusable overlay', function (string $page) {
    expect(File::get(resource_path("js/pages/{$page}")))
        ->toContain('absolute inset-0 z-10 cursor-pointer rounded-xl')
        ->toContain('focus-visible:ring-orange-500')
        ->toContain(':aria-label="todo.title"')
        ->toContain('pointer-events-auto text-muted-foreground')
        ->not->toContain('class="group grid cursor-pointer');
})->with([
    'task index' => 'tasks/Index.vue',
    'project task list' => 'projects/Show.vue',
]);

test('segmented controls use the projects muted and card surface contract', function () {
    expect(File::get(resource_path('js/pages/activity/Index.vue')))
        ->toContain('rounded-xl bg-muted p-1')
        ->toContain("'bg-card text-foreground shadow-sm'")
        ->not->toContain("'bg-foreground text-background'")
        ->and(File::get(resource_path('js/components/AppearanceTabs.vue')))
        ->toContain('rounded-xl bg-muted p-1')
        ->toContain("'bg-card text-foreground shadow-sm'")
        ->toContain('focus-visible:ring-orange-500')
        ->not->toContain('bg-white');
});

test('shared authentication controls use the warm precision interaction contract', function () {
    expect(File::get(resource_path('js/components/PasswordInput.vue')))
        ->toContain('focus-visible:ring-orange-500/25')
        ->toContain(':aria-pressed="showPassword"')
        ->toContain("'auth.common.show_password'")
        ->toContain("'auth.common.hide_password'")
        ->not->toContain(':tabindex="-1"')
        ->and(File::get(resource_path('js/components/TextLink.vue')))
        ->toContain('text-orange-700')
        ->toContain('focus-visible:ring-orange-500')
        ->not->toContain('decoration-neutral')
        ->and(File::get(resource_path('js/pages/auth/Login.vue')))
        ->not->toContain('tabindex=')
        ->and(File::get(resource_path('js/pages/auth/Register.vue')))
        ->not->toContain('tabindex=')
        ->and(File::get(resource_path('js/pages/auth/TwoFactorChallenge.vue')))
        ->toContain('focus-visible:ring-orange-500')
        ->not->toContain('decoration-neutral')
        ->and(File::get(resource_path('js/components/ui/input-otp/InputOTPSlot.vue')))
        ->toContain('data-[active=true]:border-orange-500')
        ->toContain('data-[active=true]:ring-orange-500/20')
        ->toContain('first:rounded-l-xl')
        ->toContain('motion-reduce:animate-none');
});

test('shared navigation feedback uses localized labels and the projects orange accent', function () {
    expect(File::get(resource_path('js/app.ts')))
        ->toContain("color: '#ea580c'")
        ->not->toContain("color: '#4B5563'")
        ->and(File::get(resource_path('js/components/ui/spinner/Spinner.vue')))
        ->toContain("t('common.states.loading')")
        ->and(File::get(resource_path('js/components/ui/breadcrumb/Breadcrumb.vue')))
        ->toContain("t('common.navigation.breadcrumb')")
        ->and(File::get(resource_path('js/components/ui/sidebar/SidebarRail.vue')))
        ->toContain("t('common.navigation.toggle_sidebar')")
        ->toContain('hover:after:bg-orange-500')
        ->and(File::get(resource_path('js/components/UserInfo.vue')))
        ->toContain('bg-orange-500/10')
        ->toContain('text-orange-800');
});

test('shared interaction accessibility copy exists in every supported language', function (string $locale) {
    $copy = require lang_path("{$locale}/ui.php");

    expect(data_get($copy, 'common.navigation.breadcrumb'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.navigation.toggle_sidebar'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.states.loading'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'auth.common.show_password'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'auth.common.hide_password'))
        ->toBeString()
        ->not->toBeEmpty();
})->with(['en', 'lt', 'ru']);

test('list pages share the warm precision empty state', function (string $page) {
    expect(File::get(resource_path("js/pages/{$page}")))
        ->toContain('EmptyState');
})->with([
    'activity' => 'activity/Index.vue',
    'notifications' => 'notifications/Index.vue',
    'projects' => 'projects/Index.vue',
    'project detail' => 'projects/Show.vue',
    'tasks' => 'tasks/Index.vue',
    'workspaces' => 'workspaces/Index.vue',
    'backups' => 'settings/Backup.vue',
]);

test('guest authentication uses the same left rail hierarchy as projects', function () {
    expect(File::get(resource_path('js/layouts/auth/AuthSimpleLayout.vue')))
        ->toContain('inset-y-0 left-0 w-1.5 bg-orange-500')
        ->toContain('tracking-[0.16em]')
        ->not->toContain('inset-x-0 top-0 h-1.5');
});
