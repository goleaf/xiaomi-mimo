<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskPriorityController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationAcceptanceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceManagementController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceOwnershipController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shortcut routes resolve the authenticated user's current workspace.
    Route::get('tasks', function (Request $request) {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $workspace = $user->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return Inertia::render('tasks/Index', [
                'todos' => ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 50],
                'filters' => [], 'projects' => ['data' => []], 'workspace' => ['id' => ''],
            ]);
        }

        return app(TodoController::class)->index($request, $workspace);
    })->name('todos.index');

    Route::get('projects', function (Request $request) {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $workspace = $user->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return Inertia::render('projects/Index', ['projects' => ['data' => []], 'workspace' => ['id' => '', 'name' => '']]);
        }

        return app(ProjectController::class)->index($request, $workspace);
    })->name('projects');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar');

    Route::get('activity', function (Request $request) {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $workspace = $user->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return inertia('activity/Index', ['activities' => ['data' => []]]);
        }

        return app(ActivityController::class)->index($request, $workspace);
    })->name('activity');

    // Workspaces
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('workspaces/{workspace}', [WorkspaceManagementController::class, 'show'])->name('workspaces.show');
    Route::get('workspaces/{workspace}/members', [WorkspaceManagementController::class, 'members'])->name('workspaces.members');
    Route::get('workspaces/{workspace}/configuration', [WorkspaceManagementController::class, 'configuration'])->name('workspaces.configuration');
    Route::get('workspaces/{workspace}/danger', [WorkspaceManagementController::class, 'danger'])->name('workspaces.danger');
    Route::post('workspaces/{workspace}/duplicate', [WorkspaceController::class, 'duplicate'])->name('workspaces.duplicate');
    Route::put('workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::post('workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('workspaces/{workspace}/invite', [WorkspaceInvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('workspaces.invite');
    Route::post('workspaces/{workspace}/invitations/{invitation}/resend', [WorkspaceInvitationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->scopeBindings()
        ->name('workspaces.invitations.resend');
    Route::delete('workspaces/{workspace}/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy'])
        ->scopeBindings()
        ->name('workspaces.invitations.cancel');
    Route::patch('workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'update'])
        ->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'destroy'])
        ->name('workspaces.removeMember');
    Route::post('workspaces/{workspace}/ownership', WorkspaceOwnershipController::class)
        ->name('workspaces.transferOwnership');
    Route::get('workspace-invitations/{invitation}/accept', WorkspaceInvitationAcceptanceController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('workspace-invitations.accept');

    // Projects
    Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('workspaces/{workspace}/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('workspaces/{workspace}/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::post('workspaces/{workspace}/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
    Route::put('workspaces/{workspace}/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');

    // Todos
    Route::post('workspaces/{workspace}/tasks', [TodoController::class, 'store'])->name('todos.store');
    Route::get('tasks/{todo}', [TodoController::class, 'show'])->name('todos.show');
    Route::put('tasks/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('tasks/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('tasks/{todo}/complete', [TodoController::class, 'complete'])->name('todos.complete');
    Route::post('tasks/{todo}/uncomplete', [TodoController::class, 'uncomplete'])->name('todos.uncomplete');
    Route::post('tasks/{todo}/archive', [TodoController::class, 'archive'])->name('todos.archive');
    Route::post('tasks/{todo}/restore', [TodoController::class, 'restore'])->name('todos.restore');
    Route::post('tasks/{todo}/pin', [TodoController::class, 'pin'])->name('todos.pin');
    Route::post('tasks/{todo}/favorite', [TodoController::class, 'favorite'])->name('todos.favorite');
    Route::post('tasks/{todo}/duplicate', [TodoController::class, 'duplicate'])->name('todos.duplicate');
    Route::put('workspaces/{workspace}/tasks/reorder', [TodoController::class, 'reorder'])->name('todos.reorder');
    Route::post('workspaces/{workspace}/tasks/bulk', [TodoController::class, 'bulk'])->name('todos.bulk');

    // Comments
    Route::post('tasks/{todo}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Checklists
    Route::post('tasks/{todo}/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    Route::put('checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('checklists.destroy');
    Route::post('checklists/{checklist}/items', [ChecklistController::class, 'storeItem'])->name('checklistItems.store');
    Route::patch('checklist-items/{item}/toggle', [ChecklistController::class, 'toggleItem'])->name('checklistItems.toggle');
    Route::delete('checklist-items/{item}', [ChecklistController::class, 'destroyItem'])->name('checklistItems.destroy');

    // Labels
    Route::get('workspaces/{workspace}/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::post('workspaces/{workspace}/labels', [LabelController::class, 'store'])->name('labels.store');
    Route::put('workspaces/{workspace}/labels/{label}', [LabelController::class, 'update'])
        ->scopeBindings()
        ->name('labels.update');
    Route::delete('workspaces/{workspace}/labels/{label}', [LabelController::class, 'destroy'])
        ->scopeBindings()
        ->name('labels.destroy');
    Route::post('workspaces/{workspace}/tasks/{todo}/labels', [LabelController::class, 'attach'])
        ->scopeBindings()
        ->name('labels.attach');
    Route::delete('workspaces/{workspace}/tasks/{todo}/labels/{label}', [LabelController::class, 'detach'])
        ->scopeBindings()
        ->name('labels.detach');

    // Tags
    Route::get('workspaces/{workspace}/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('workspaces/{workspace}/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('workspaces/{workspace}/tags/{tag}', [TagController::class, 'update'])
        ->scopeBindings()
        ->name('tags.update');
    Route::delete('workspaces/{workspace}/tags/{tag}', [TagController::class, 'destroy'])
        ->scopeBindings()
        ->name('tags.destroy');
    Route::post('workspaces/{workspace}/tasks/{todo}/tags', [TagController::class, 'attach'])
        ->scopeBindings()
        ->name('tags.attach');
    Route::delete('workspaces/{workspace}/tasks/{todo}/tags/{tag}', [TagController::class, 'detach'])
        ->scopeBindings()
        ->name('tags.detach');

    // Task statuses and priorities
    Route::get('workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'index'])->name('task-statuses.index');
    Route::post('workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'store'])->name('task-statuses.store');
    Route::put('workspaces/{workspace}/task-statuses/reorder', [TaskStatusController::class, 'reorder'])->name('task-statuses.reorder');
    Route::put('workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'update'])
        ->scopeBindings()
        ->name('task-statuses.update');
    Route::patch('workspaces/{workspace}/task-statuses/{taskStatus}/manage', [TaskStatusController::class, 'manage'])
        ->scopeBindings()
        ->name('task-statuses.manage');
    Route::delete('workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'destroy'])
        ->scopeBindings()
        ->name('task-statuses.destroy');

    Route::get('workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'index'])->name('task-priorities.index');
    Route::post('workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'store'])->name('task-priorities.store');
    Route::put('workspaces/{workspace}/task-priorities/reorder', [TaskPriorityController::class, 'reorder'])->name('task-priorities.reorder');
    Route::put('workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'update'])
        ->scopeBindings()
        ->name('task-priorities.update');
    Route::patch('workspaces/{workspace}/task-priorities/{taskPriority}/manage', [TaskPriorityController::class, 'manage'])
        ->scopeBindings()
        ->name('task-priorities.manage');
    Route::delete('workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'destroy'])
        ->scopeBindings()
        ->name('task-priorities.destroy');

    // Attachments
    Route::post('tasks/{todo}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // Activity
    Route::get('workspaces/{workspace}/activity', [ActivityController::class, 'index'])->name('activity.index');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // User Preferences
    Route::put('settings/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');

    // Export/Import
    Route::get('workspaces/{workspace}/export/{format}', [ExportController::class, 'export'])->name('export');
    Route::post('workspaces/{workspace}/import', [ImportController::class, 'import'])->name('import');

    // Application database backups
    Route::middleware(['can:manageDatabaseBackups', 'password.confirm'])->group(function () {
        Route::post('backup', [BackupController::class, 'backup'])
            ->middleware('throttle:3,1')
            ->name('backup.create');
        Route::get('backups', [BackupController::class, 'list'])->name('backup.list');
        Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])
            ->middleware('throttle:1,1')
            ->whereUuid('backup')
            ->name('backup.restore');
        Route::get('backups/{backup}/download', [BackupController::class, 'download'])
            ->whereUuid('backup')
            ->name('backup.download');
    });
});

require __DIR__.'/settings.php';
