<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskPriorityController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceInvitationController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\WorkspaceOwnershipController;
use Illuminate\Support\Facades\Route;

$registerApiRoutes = function (bool $versioned): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:api-registration')
        ->name('auth.register');

    Route::middleware('auth:sanctum')->group(function () use ($versioned): void {
        Route::get('user', UserController::class)->name('user.show');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('workspaces', [WorkspaceController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('workspaces.index');
        Route::post('workspaces', [WorkspaceController::class, 'store'])
            ->middleware('abilities:workspaces:write')
            ->name('workspaces.store');
        Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])
            ->middleware('abilities:workspaces:read')
            ->name('workspaces.show');
        Route::post('workspaces/{workspace}/duplicate', [WorkspaceController::class, 'duplicate'])
            ->middleware('abilities:workspaces:write')
            ->name('workspaces.duplicate');
        Route::put('workspaces/{workspace}', [WorkspaceController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->name('workspaces.update');
        Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->name('workspaces.destroy');
        Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('workspace-members.index');
        Route::patch('workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->name('workspace-members.update');
        Route::delete('workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->name('workspace-members.destroy');
        Route::get('workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('workspace-invitations.index');
        Route::post('workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store'])
            ->middleware(['abilities:workspaces:write', 'throttle:10,1'])
            ->name('workspace-invitations.store');
        Route::post('workspaces/{workspace}/invitations/{invitation}/resend', [WorkspaceInvitationController::class, 'resend'])
            ->middleware(['abilities:workspaces:write', 'throttle:6,1'])
            ->scopeBindings()
            ->name('workspace-invitations.resend');
        Route::delete('workspaces/{workspace}/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('workspace-invitations.destroy');
        Route::post('workspaces/{workspace}/ownership', WorkspaceOwnershipController::class)
            ->middleware('abilities:workspaces:write')
            ->name('workspace-ownership.store');

        Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])
            ->middleware('abilities:projects:read')
            ->name('projects.index');
        Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])
            ->middleware('abilities:projects:write')
            ->name('projects.store');
        Route::get(
            $versioned ? 'workspaces/{workspace}/projects/{project}' : 'projects/{project}',
            [ProjectController::class, $versioned ? 'showScoped' : 'show'],
        )->middleware('abilities:projects:read')->name('projects.show');
        Route::put(
            $versioned ? 'workspaces/{workspace}/projects/{project}' : 'projects/{project}',
            [ProjectController::class, $versioned ? 'updateScoped' : 'update'],
        )->middleware('abilities:projects:write')->name('projects.update');
        Route::delete(
            $versioned ? 'workspaces/{workspace}/projects/{project}' : 'projects/{project}',
            [ProjectController::class, $versioned ? 'destroyScoped' : 'destroy'],
        )->middleware('abilities:projects:write')->name('projects.destroy');

        Route::get('workspaces/{workspace}/tasks', [TodoController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('tasks.index');
        Route::post('workspaces/{workspace}/tasks', [TodoController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('tasks.store');
        $taskPath = $versioned ? 'workspaces/{workspace}/tasks/{todo}' : 'tasks/{todo}';
        Route::get($taskPath, [TodoController::class, $versioned ? 'showScoped' : 'show'])
            ->middleware('abilities:tasks:read')
            ->name('tasks.show');
        Route::put($taskPath, [TodoController::class, $versioned ? 'updateScoped' : 'update'])
            ->middleware('abilities:tasks:write')
            ->name('tasks.update');
        Route::delete($taskPath, [TodoController::class, $versioned ? 'destroyScoped' : 'destroy'])
            ->middleware('abilities:tasks:write')
            ->name('tasks.destroy');
        Route::post($taskPath.'/complete', [TodoController::class, $versioned ? 'completeScoped' : 'complete'])
            ->middleware('abilities:tasks:write')
            ->name('tasks.complete');
        Route::post($taskPath.'/uncomplete', [TodoController::class, $versioned ? 'uncompleteScoped' : 'uncomplete'])
            ->middleware('abilities:tasks:write')
            ->name('tasks.uncomplete');

        Route::get('tasks/{todo}/comments', [CommentController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('comments.index');
        Route::post('tasks/{todo}/comments', [CommentController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('comments.store');
        $commentPath = $versioned ? 'tasks/{todo}/comments/{comment}' : 'comments/{comment}';
        Route::put($commentPath, [CommentController::class, $versioned ? 'updateScoped' : 'update'])
            ->middleware('abilities:tasks:write')
            ->name('comments.update');
        Route::delete($commentPath, [CommentController::class, $versioned ? 'destroyScoped' : 'destroy'])
            ->middleware('abilities:tasks:write')
            ->name('comments.destroy');

        Route::get('tasks/{todo}/checklists', [ChecklistController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('checklists.index');
        Route::post('tasks/{todo}/checklists', [ChecklistController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('checklists.store');
        Route::post(
            $versioned ? 'tasks/{todo}/checklists/{checklist}/items' : 'checklists/{checklist}/items',
            [ChecklistController::class, $versioned ? 'storeItemScoped' : 'storeItem'],
        )->middleware('abilities:tasks:write')->name('checklist-items.store');
        Route::patch(
            $versioned
                ? 'tasks/{todo}/checklists/{checklist}/items/{item}/toggle'
                : 'checklist-items/{item}/toggle',
            [ChecklistController::class, $versioned ? 'toggleItemScoped' : 'toggleItem'],
        )->middleware('abilities:tasks:write')->name('checklist-items.toggle');

        Route::get('workspaces/{workspace}/labels', [LabelController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('labels.index');
        Route::post('workspaces/{workspace}/labels', [LabelController::class, 'store'])
            ->middleware('abilities:workspaces:write')
            ->name('labels.store');
        Route::put('workspaces/{workspace}/labels/{label}', [LabelController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('labels.update');
        Route::delete('workspaces/{workspace}/labels/{label}', [LabelController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('labels.destroy');

        Route::get('workspaces/{workspace}/tags', [TagController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('tags.index');
        Route::post('workspaces/{workspace}/tags', [TagController::class, 'store'])
            ->middleware('abilities:workspaces:write')
            ->name('tags.store');
        Route::put('workspaces/{workspace}/tags/{tag}', [TagController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('tags.update');
        Route::delete('workspaces/{workspace}/tags/{tag}', [TagController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('tags.destroy');

        if (! $versioned) {
            Route::put('labels/{label}', [LabelController::class, 'legacyUpdate'])
                ->middleware('abilities:workspaces:write')
                ->name('labels.legacy-update');
            Route::delete('labels/{label}', [LabelController::class, 'legacyDestroy'])
                ->middleware('abilities:workspaces:write')
                ->name('labels.legacy-destroy');
            Route::put('tags/{tag}', [TagController::class, 'legacyUpdate'])
                ->middleware('abilities:workspaces:write')
                ->name('tags.legacy-update');
            Route::delete('tags/{tag}', [TagController::class, 'legacyDestroy'])
                ->middleware('abilities:workspaces:write')
                ->name('tags.legacy-destroy');
        }

        Route::get('workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('task-statuses.index');
        Route::post('workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'store'])
            ->middleware('abilities:workspaces:write')
            ->name('task-statuses.store');
        Route::put('workspaces/{workspace}/task-statuses/reorder', [TaskStatusController::class, 'reorder'])
            ->middleware('abilities:workspaces:write')
            ->name('task-statuses.reorder');
        Route::put('workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-statuses.update');
        Route::patch('workspaces/{workspace}/task-statuses/{taskStatus}/manage', [TaskStatusController::class, 'manage'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-statuses.manage');
        Route::delete('workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-statuses.destroy');

        Route::get('workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'index'])
            ->middleware('abilities:workspaces:read')
            ->name('task-priorities.index');
        Route::post('workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'store'])
            ->middleware('abilities:workspaces:write')
            ->name('task-priorities.store');
        Route::put('workspaces/{workspace}/task-priorities/reorder', [TaskPriorityController::class, 'reorder'])
            ->middleware('abilities:workspaces:write')
            ->name('task-priorities.reorder');
        Route::put('workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'update'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-priorities.update');
        Route::patch('workspaces/{workspace}/task-priorities/{taskPriority}/manage', [TaskPriorityController::class, 'manage'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-priorities.manage');
        Route::delete('workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'destroy'])
            ->middleware('abilities:workspaces:write')
            ->scopeBindings()
            ->name('task-priorities.destroy');

        Route::get('tasks/{todo}/reminders', [ReminderController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('reminders.index');
        Route::post('tasks/{todo}/reminders', [ReminderController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('reminders.store');
        Route::delete(
            $versioned ? 'tasks/{todo}/reminders/{reminder}' : 'reminders/{reminder}',
            [ReminderController::class, $versioned ? 'destroyScoped' : 'destroy'],
        )->middleware('abilities:tasks:write')->name('reminders.destroy');

        Route::get('tasks/{todo}/attachments', [AttachmentController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('attachments.index');
        Route::post('tasks/{todo}/attachments', [AttachmentController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('attachments.store');
        $attachmentPath = $versioned ? 'tasks/{todo}/attachments/{attachment}' : 'attachments/{attachment}';
        Route::delete($attachmentPath, [AttachmentController::class, $versioned ? 'destroyScoped' : 'destroy'])
            ->middleware('abilities:tasks:write')
            ->name('attachments.destroy');
        Route::get($attachmentPath.'/download', [AttachmentController::class, $versioned ? 'downloadScoped' : 'download'])
            ->middleware('abilities:tasks:read')
            ->name('attachments.download');
    });
};

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('api.version:1')
    ->scopeBindings()
    ->group(fn () => $registerApiRoutes(true));

Route::name('api.legacy.')
    ->middleware('api.version:legacy')
    ->group(fn () => $registerApiRoutes(false));
