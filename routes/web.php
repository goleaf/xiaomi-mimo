<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Helper to get current workspace
    $ws = fn () => request()->user()->currentWorkspace();

    // Shortcut routes — resolve current workspace
    Route::get('tasks', function () {
        $workspace = $this->user()->currentWorkspace();
        if (! $workspace) {
            return Inertia::render('tasks/Index', [
                'todos' => ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 50],
                'filters' => [], 'projects' => ['data' => []], 'workspace' => ['id' => ''],
            ]);
        }
        return app(TodoController::class)->index(request(), $workspace);
    })->middleware('auth')->name('tasks');

    Route::get('projects', function () {
        $workspace = $this->user()->currentWorkspace();
        if (! $workspace) {
            return Inertia::render('projects/Index', ['projects' => ['data' => []], 'workspace' => ['id' => '', 'name' => '']]);
        }
        return app(ProjectController::class)->index(request(), $workspace);
    })->middleware('auth')->name('projects');

    Route::get('calendar', function () {
        $workspace = $this->user()->currentWorkspace();
        $todos = $workspace ? $workspace->todos()->active()->whereNotNull('due_date')->get()->toArray() : [];
        return inertia('calendar/Index', ['todos' => $todos]);
    })->middleware('auth')->name('calendar');

    Route::get('activity', function () {
        $workspace = $this->user()->currentWorkspace();
        if (! $workspace) {
            return inertia('activity/Index', ['activities' => ['data' => []]]);
        }
        return app(ActivityController::class)->index(request(), $workspace);
    })->middleware('auth')->name('activity');

    // Workspaces
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::put('workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::post('workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('workspaces/{workspace}/invite', [WorkspaceController::class, 'invite'])->name('workspaces.invite');
    Route::delete('workspaces/{workspace}/members/{userId}', [WorkspaceController::class, 'removeMember'])->name('workspaces.removeMember');

    // Projects
    Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])->name('workspaces.projects.index');
    Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])->name('workspaces.projects.store');
    Route::get('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('workspaces/{workspace}/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('workspaces/{workspace}/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::post('workspaces/{workspace}/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
    Route::put('workspaces/{workspace}/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');

    // Todos
    Route::post('workspaces/{workspace}/tasks', [TodoController::class, 'store'])->name('workspaces.tasks.store');
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
    Route::put('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
    Route::post('tasks/{todo}/labels', [LabelController::class, 'attach'])->name('labels.attach');
    Route::delete('tasks/{todo}/labels/{label}', [LabelController::class, 'detach'])->name('labels.detach');

    // Tags
    Route::get('workspaces/{workspace}/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('workspaces/{workspace}/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('tasks/{todo}/tags', [TagController::class, 'attach'])->name('tags.attach');
    Route::delete('tasks/{todo}/tags/{tag}', [TagController::class, 'detach'])->name('tags.detach');

    // Attachments
    Route::post('tasks/{todo}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // Activity
    Route::get('workspaces/{workspace}/activity', [ActivityController::class, 'index'])->name('workspaces.activity.index');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // User Preferences
    Route::put('settings/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');

    // Export/Import
    Route::get('workspaces/{workspace}/export/{format}', [ExportController::class, 'export'])->name('export');
    Route::post('workspaces/{workspace}/import', [ImportController::class, 'import'])->name('import');

    // Backup
    Route::post('backup', [BackupController::class, 'backup'])->name('backup.create');
    Route::get('backups', [BackupController::class, 'list'])->name('backup.list');
    Route::post('backups/{filename}/restore', [BackupController::class, 'restore'])->name('backup.restore');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
});

require __DIR__.'/settings.php';
