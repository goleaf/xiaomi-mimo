<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

test('every active page header action uses the shared large button contract', function (string $page, int $actionCount) {
    $source = File::get(resource_path("js/pages/{$page}"));
    $actions = Str::betweenFirst(
        $source,
        '<template #actions>',
        '</template>',
    );

    expect(substr_count($actions, 'size="lg"'))
        ->toBe($actionCount)
        ->and($actions)
        ->not->toContain('min-h-11')
        ->not->toContain('bg-orange-600')
        ->not->toContain('rounded-xl');
})->with([
    'notifications' => ['notifications/Index.vue', 1],
    'projects' => ['projects/Index.vue', 1],
    'project detail' => ['projects/Show.vue', 5],
    'tasks' => ['tasks/Index.vue', 1],
    'task detail' => ['tasks/Show.vue', 3],
    'workspaces' => ['workspaces/Index.vue', 1],
]);

test('header mutations expose shared inert loading states', function () {
    $notifications = File::get(resource_path('js/pages/notifications/Index.vue'));
    $project = File::get(resource_path('js/pages/projects/Show.vue'));
    $task = File::get(resource_path('js/pages/tasks/Show.vue'));

    expect($notifications)
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="markingAll" />')
        ->and($project)
        ->toContain("type ProjectHeaderAction = 'duplicate' | 'archive' | 'restore'")
        ->toContain('const processingProjectAction = ref<ProjectHeaderAction | null>(null)')
        ->toContain('v-if="processingProjectAction ===')
        ->toContain('onFinish: () =>')
        ->and($task)
        ->toContain('const updatingCompletion = ref(false)')
        ->toContain('<Spinner v-if="updatingCompletion" />')
        ->toContain('onFinish: () =>');
});

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

test('remaining active secondary actions reuse the shared large control rhythm', function () {
    $emptyState = File::get(resource_path('js/components/shared/EmptyState.vue'));
    $calendar = File::get(resource_path('js/pages/calendar/Index.vue'));
    $taskDetail = File::get(resource_path('js/components/task/TaskDetail.vue'));

    expect($emptyState)
        ->toContain('size="lg"')
        ->not->toContain('bg-orange-600 text-white hover:bg-orange-700')
        ->and($calendar)
        ->toContain('size="lg"')
        ->not->toContain('class="min-h-11 cursor-pointer rounded-xl"')
        ->and(substr_count($taskDetail, 'size="lg"'))
        ->toBeGreaterThanOrEqual(3)
        ->and($taskDetail)
        ->not->toContain('class="h-10 rounded-xl text-sm"')
        ->not->toContain('class="min-h-11 rounded-xl"');
});

test('data import exposes an inert shared loading state', function () {
    expect(File::get(resource_path('js/pages/settings/Export.vue')))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('const importingFormat = ref<ImportFormat | null>(null)')
        ->toContain(':disabled="Boolean(importingFormat)"')
        ->toContain(':aria-busy="importingFormat === format"')
        ->toContain('<Spinner v-if="importingFormat === format" />')
        ->toContain('onFinish: () =>')
        ->toContain('pointer-events-none opacity-50');
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

test('active create dialogs reuse shared controls for processing states', function (string $file) {
    expect(File::get(resource_path("js/components/{$file}")))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="form.processing" />')
        ->toContain('size="lg"')
        ->not->toContain('class="h-11 rounded-xl"')
        ->not->toContain('bg-orange-600 text-white hover:bg-orange-700');
})->with([
    'project create' => 'project/ProjectCreateDialog.vue',
    'task create' => 'task/TaskCreateDialog.vue',
]);

test('workspace creation reuses shared controls for complete processing states', function () {
    expect(File::get(resource_path('js/pages/workspaces/Index.vue')))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="form.processing" />')
        ->toContain(':aria-invalid="Boolean(form.errors.description)"')
        ->toContain(':disabled="form.processing"')
        ->toContain('size="lg"')
        ->not->toContain('class="h-11 rounded-xl"')
        ->not->toContain('bg-orange-600 text-white hover:bg-orange-700');
});

test('settings save forms reuse shared large loading actions', function (string $page) {
    expect(File::get(resource_path("js/pages/settings/{$page}.vue")))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="form.processing" />')
        ->toContain(':disabled="form.processing"')
        ->toContain('size="lg"')
        ->not->toContain('bg-orange-600 text-white hover:bg-orange-700');
})->with([
    'preferences' => 'Preferences',
    'notifications' => 'Notifications',
]);

test('notification option copy keeps a readable mobile hierarchy', function () {
    expect(File::get(resource_path('js/pages/settings/Notifications.vue')))
        ->toContain('flex-col items-start gap-0')
        ->toContain('leading-5')
        ->toContain('text-muted-foreground');
});

test('member actions reuse shared loading and large dialog controls', function () {
    expect(File::get(resource_path('js/pages/settings/Members.vue')))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="inviteForm.processing" />')
        ->toContain('<Spinner v-if="removeForm.processing" />')
        ->toContain(':disabled="inviteForm.processing"')
        ->toContain('size="lg"')
        ->not->toContain('LoaderCircle')
        ->not->toContain('class="min-h-11 cursor-pointer rounded-xl"');
});

test('shared confirmations use the shared large loading action contract', function () {
    expect(File::get(resource_path('js/components/shared/WorkspaceConfirmDialog.vue')))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="processing" />')
        ->toContain('size="lg"')
        ->not->toContain('class="min-h-11 cursor-pointer rounded-xl"')
        ->not->toContain('bg-orange-600 text-white hover:bg-orange-700');
});

test('account deletion uses inert processing and shared loading feedback', function () {
    expect(File::get(resource_path('js/components/DeleteUser.vue')))
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('disable-while-processing')
        ->toContain('<Spinner v-if="processing" />');
});

test('remaining settings forms expose complete processing states', function () {
    $backup = File::get(resource_path('js/pages/settings/Backup.vue'));
    $profile = File::get(resource_path('js/pages/settings/Profile.vue'));
    $security = File::get(resource_path('js/pages/settings/Security.vue'));

    expect($backup)
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="creating" />')
        ->toContain('size="lg"')
        ->and($profile)
        ->toContain('size="lg"')
        ->and(substr_count($profile, ':disabled="profileForm.processing"'))
        ->toBeGreaterThanOrEqual(2)
        ->and(substr_count($security, ':disabled="passwordForm.processing"'))
        ->toBeGreaterThanOrEqual(4)
        ->and(substr_count($security, 'size="lg"'))
        ->toBeGreaterThanOrEqual(3);
});

test('task editing uses shared loading actions and locks mutable fields', function () {
    $source = File::get(resource_path('js/pages/tasks/Show.vue'));

    expect($source)
        ->toContain("import { Spinner } from '@/components/ui/spinner'")
        ->toContain('<Spinner v-if="editForm.processing" />')
        ->not->toContain('LoaderCircle')
        ->and(substr_count($source, ':disabled="editForm.processing"'))
        ->toBeGreaterThanOrEqual(7)
        ->and(substr_count($source, 'size="lg"'))
        ->toBeGreaterThanOrEqual(2);
});

test('project creation selectors expose warm precision interaction states', function () {
    expect(File::get(resource_path('js/components/project/ProjectCreateDialog.vue')))
        ->toContain(':aria-invalid="Boolean(form.errors.description)"')
        ->toContain('border-orange-500/50 bg-orange-500/[0.08] shadow-sm')
        ->toContain('motion-reduce:transition-none')
        ->toContain(':disabled="form.processing"');
});

test('task creation fields expose complete invalid and disabled states', function () {
    expect(File::get(resource_path('js/components/task/TaskCreateDialog.vue')))
        ->toContain(':aria-invalid="Boolean(form.errors.description)"')
        ->toContain(':aria-invalid="Boolean(form.errors.priority)"')
        ->toContain(':aria-invalid="Boolean(form.errors.due_date)"')
        ->toContain('form.errors.recurring_rule')
        ->toContain(':disabled="!form.is_recurring || form.processing"')
        ->toContain('<InputError :message="form.errors.priority" />')
        ->toContain('<InputError :message="form.errors.due_date" />')
        ->toContain('<InputError :message="form.errors.recurring_rule" />');
});

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

test('authentication submissions share the large projects action rhythm', function (string $page) {
    expect(File::get(resource_path("js/pages/auth/{$page}.vue")))
        ->toContain('disable-while-processing')
        ->toContain('size="lg"')
        ->toContain('<Spinner v-if="processing" />');
})->with([
    'login' => 'Login',
    'registration' => 'Register',
    'forgot password' => 'ForgotPassword',
    'reset password' => 'ResetPassword',
    'password confirmation' => 'ConfirmPassword',
    'email verification' => 'VerifyEmail',
    'two factor challenge' => 'TwoFactorChallenge',
]);

test('authentication credential controls expose invalid state', function (string $page) {
    expect(File::get(resource_path("js/pages/auth/{$page}.vue")))
        ->toContain(':aria-invalid="Boolean(errors.');
})->with([
    'login' => 'Login',
    'registration' => 'Register',
    'forgot password' => 'ForgotPassword',
    'reset password' => 'ResetPassword',
    'password confirmation' => 'ConfirmPassword',
    'two factor challenge' => 'TwoFactorChallenge',
]);

test('passkey verification uses the shared large loading action', function () {
    expect(File::get(resource_path('js/components/PasskeyVerify.vue')))
        ->toContain('size="lg"')
        ->toContain('<Spinner v-if="isLoading" />')
        ->toContain(':disabled="isLoading"');
});

test('shared navigation feedback uses localized labels and the projects orange accent', function () {
    expect(File::get(resource_path('js/app.ts')))
        ->toContain("color: '#ea580c'")
        ->not->toContain("color: '#4B5563'")
        ->and(File::get(resource_path('js/components/ui/spinner/Spinner.vue')))
        ->toContain("t('common.states.loading')")
        ->and(File::get(resource_path('js/components/ui/breadcrumb/Breadcrumb.vue')))
        ->toContain("t('common.navigation.breadcrumb')")
        ->and(File::get(resource_path('js/components/ui/breadcrumb/BreadcrumbEllipsis.vue')))
        ->toContain("t('common.navigation.more')")
        ->and(File::get(resource_path('js/components/ui/sidebar/SidebarTrigger.vue')))
        ->toContain("t('common.navigation.toggle_sidebar')")
        ->and(File::get(resource_path('js/components/ui/sidebar/SidebarRail.vue')))
        ->toContain("t('common.navigation.toggle_sidebar')")
        ->toContain('hover:after:bg-orange-500')
        ->and(File::get(resource_path('js/components/UserInfo.vue')))
        ->toContain('bg-orange-500/10')
        ->toContain('text-orange-800');
});

test('shared transient surfaces use the warm precision interaction contract', function () {
    expect(File::get(resource_path('js/components/ui/dropdown-menu/DropdownMenuContent.vue')))
        ->toContain('rounded-xl')
        ->toContain('border-border/80')
        ->toContain('motion-reduce:data-[state=open]:animate-none')
        ->and(File::get(resource_path('js/components/ui/dropdown-menu/DropdownMenuItem.vue')))
        ->toContain('min-h-10')
        ->toContain('focus:bg-orange-500/10')
        ->and(File::get(resource_path('js/components/ui/select/SelectContent.vue')))
        ->toContain('rounded-xl')
        ->toContain('border-border/80')
        ->and(File::get(resource_path('js/components/ui/select/SelectItem.vue')))
        ->toContain('min-h-10')
        ->toContain('focus:bg-orange-500/10')
        ->toContain('text-orange-600')
        ->and(File::get(resource_path('js/components/ui/tooltip/TooltipContent.vue')))
        ->toContain('rounded-lg')
        ->toContain('border-orange-500/15')
        ->toContain('motion-reduce:animate-none');
});

test('shared controls use warm checked focus and feedback states', function () {
    expect(File::get(resource_path('js/components/ui/checkbox/Checkbox.vue')))
        ->toContain('data-[state=checked]:bg-orange-600')
        ->toContain('focus-visible:ring-orange-500/25')
        ->toContain('rounded-md')
        ->toContain('motion-reduce:transition-none')
        ->and(File::get(resource_path('js/components/ui/alert/index.ts')))
        ->toContain('rounded-xl')
        ->toContain('border-border/80')
        ->toContain('border-destructive/20')
        ->toContain('bg-destructive/[0.06]')
        ->and(File::get(resource_path('js/components/ui/alert/AlertTitle.vue')))
        ->not->toContain('line-clamp-1')
        ->and(File::get(resource_path('js/components/ui/badge/index.ts')))
        ->toContain('focus-visible:ring-orange-500/25')
        ->toContain('hover:border-orange-500/25')
        ->toContain('motion-reduce:transition-none')
        ->and(File::get(resource_path('js/components/ui/button/index.ts')))
        ->toContain('motion-reduce:transition-none');
});

test('shared form feedback uses semantic warm precision states', function () {
    expect(File::get(resource_path('js/components/ui/alert/index.ts')))
        ->toContain('border-emerald-500/20')
        ->toContain('bg-emerald-500/[0.07]')
        ->toContain('border-amber-500/25')
        ->toContain('bg-amber-500/[0.08]')
        ->and(File::get(resource_path('js/components/ui/alert/Alert.vue')))
        ->toContain('props.variant === "success" ? "status" : "alert"')
        ->and(File::get(resource_path('js/components/InputError.vue')))
        ->toContain('CircleAlert')
        ->toContain('text-destructive')
        ->toContain('role="alert"')
        ->toContain('aria-live="polite"')
        ->not->toContain('text-red-');
});

test('authentication status messages use the shared success surface', function (string $page) {
    expect(File::get(resource_path("js/pages/auth/{$page}.vue")))
        ->toContain('<Alert')
        ->toContain('variant="success"')
        ->toContain('<AlertDescription')
        ->not->toContain('text-green-600');
})->with([
    'login' => 'Login',
    'forgot password' => 'ForgotPassword',
    'verify email' => 'VerifyEmail',
]);

test('profile feedback uses semantic alerts and deterministic upload progress', function () {
    expect(File::get(resource_path('js/pages/settings/Profile.vue')))
        ->toContain('variant="warning"')
        ->toContain('role="progressbar"')
        ->toContain('bg-orange-600')
        ->toContain('motion-reduce:transition-none')
        ->not->toContain('<progress')
        ->not->toContain('bg-amber-50')
        ->and(File::get(resource_path('js/components/DeleteUser.vue')))
        ->toContain('<Alert variant="destructive">')
        ->not->toContain('bg-red-50');
});

test('active forms reuse shared field errors', function (string $component) {
    expect(File::get(resource_path("js/{$component}")))
        ->toContain('InputError')
        ->not->toContain('class="text-sm text-destructive"');
})->with([
    'task edit form' => 'pages/tasks/Show.vue',
    'member invitation form' => 'pages/settings/Members.vue',
    'security form' => 'pages/settings/Security.vue',
]);

test('security page consumes the dedicated two factor feature props', function () {
    expect(File::get(resource_path('js/pages/settings/Security.vue')))
        ->toContain('canManageTwoFactor: boolean')
        ->toContain('twoFactorEnabled?: boolean')
        ->toContain('<Card v-if="canManageTwoFactor">')
        ->toContain('v-if="twoFactorEnabled"')
        ->not->toContain('user.two_factor_enabled');
});

test('shared and page loading states respect reduced motion', function (string $component) {
    expect(File::get(resource_path("js/{$component}")))
        ->toContain('motion-reduce:animate-none');
})->with([
    'shared spinner' => 'components/ui/spinner/Spinner.vue',
    'shared skeleton' => 'components/ui/skeleton/Skeleton.vue',
    'empty state' => 'components/shared/EmptyState.vue',
    'workspace switcher' => 'components/workspace/WorkspaceSwitcher.vue',
    'two factor setup' => 'components/TwoFactorSetupModal.vue',
    'two factor recovery codes' => 'components/TwoFactorRecoveryCodes.vue',
]);

test('segmented and inline controls respect reduced motion', function (string $component) {
    expect(File::get(resource_path("js/{$component}")))
        ->toContain('motion-reduce:transition-none');
})->with([
    'appearance tabs' => 'components/AppearanceTabs.vue',
    'projects view switcher' => 'pages/projects/Index.vue',
    'notification filters' => 'pages/notifications/Index.vue',
    'activity filters' => 'pages/activity/Index.vue',
    'calendar view switcher' => 'pages/calendar/Index.vue',
    'settings navigation' => 'layouts/settings/Layout.vue',
    'two factor challenge toggle' => 'pages/auth/TwoFactorChallenge.vue',
]);

test('security feedback surfaces use the shared warm card treatment', function () {
    expect(File::get(resource_path('js/components/TwoFactorSetupModal.vue')))
        ->toContain('rounded-2xl border border-border/80 bg-card')
        ->toContain('shadow-[0_16px_45px_-32px_rgba(234,88,12,0.5)]')
        ->and(File::get(resource_path('js/components/TwoFactorRecoveryCodes.vue')))
        ->toContain('rounded-xl border border-border/80 bg-muted/50')
        ->and(File::get(resource_path('js/components/PasskeyItem.vue')))
        ->toContain('rounded-lg border border-border/80 bg-muted/60');
});

test('shared overlays and feedback surfaces use warm focus and reduced motion', function () {
    expect(File::get(resource_path('js/components/ui/dialog/DialogContent.vue')))
        ->toContain('bg-card')
        ->toContain('focus-visible:ring-orange-500')
        ->toContain("t('common.actions.close')")
        ->and(File::get(resource_path('js/components/ui/dialog/DialogOverlay.vue')))
        ->toContain('bg-black/65')
        ->toContain('backdrop-blur-[2px]')
        ->toContain('motion-reduce:data-[state=open]:animate-none')
        ->and(File::get(resource_path('js/components/ui/sheet/SheetContent.vue')))
        ->toContain('bg-card')
        ->toContain('focus-visible:ring-orange-500')
        ->toContain("closeLabel ?? t('common.actions.close')")
        ->and(File::get(resource_path('js/components/ui/sheet/SheetOverlay.vue')))
        ->toContain('bg-black/65')
        ->toContain('backdrop-blur-[2px]')
        ->and(File::get(resource_path('js/components/ui/sonner/Sonner.vue')))
        ->toContain('resolvedAppearance')
        ->toContain('0 24px 70px -36px')
        ->toContain('motion-reduce:animate-none');
});

test('shared transient accessibility copy uses semantic translations', function () {
    expect(File::get(resource_path('js/components/ui/sidebar/Sidebar.vue')))
        ->toContain("t('common.navigation.sidebar')")
        ->toContain("t('common.navigation.sidebar_description')")
        ->not->toContain('<SheetTitle>Sidebar</SheetTitle>')
        ->and(File::get(resource_path('js/components/ui/sonner/Sonner.vue')))
        ->toContain("t('common.toast.notifications')")
        ->toContain('t("common.toast.close")');
});

test('shared interaction accessibility copy exists in every supported language', function (string $locale) {
    $copy = require lang_path("{$locale}/ui.php");

    expect(data_get($copy, 'common.navigation.breadcrumb'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.navigation.toggle_sidebar'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.navigation.more'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.navigation.sidebar'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.navigation.sidebar_description'))
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
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.toast.close'))
        ->toBeString()
        ->not->toBeEmpty()
        ->and(data_get($copy, 'common.toast.notifications'))
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
