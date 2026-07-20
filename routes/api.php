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
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceOwnershipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/duplicate', [WorkspaceController::class, 'duplicate'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update'])
        ->middleware('abilities:workspaces:write');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])
        ->middleware('abilities:workspaces:write');
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::patch('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'update'])
        ->middleware('abilities:workspaces:write');
    Route::delete('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'destroy'])
        ->middleware('abilities:workspaces:write');
    Route::get('/workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store'])
        ->middleware(['abilities:workspaces:write', 'throttle:10,1']);
    Route::post('/workspaces/{workspace}/invitations/{invitation}/resend', [WorkspaceInvitationController::class, 'resend'])
        ->middleware(['abilities:workspaces:write', 'throttle:6,1'])
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::post('/workspaces/{workspace}/ownership', WorkspaceOwnershipController::class)
        ->middleware('abilities:workspaces:write');

    // Projects
    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index'])
        ->middleware('abilities:projects:read');
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store'])
        ->middleware('abilities:projects:write');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->middleware('abilities:projects:read');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])
        ->middleware('abilities:projects:write');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->middleware('abilities:projects:write');

    // Todos
    Route::get('/workspaces/{workspace}/tasks', [TodoController::class, 'index'])
        ->middleware('abilities:tasks:read');
    Route::post('/workspaces/{workspace}/tasks', [TodoController::class, 'store'])
        ->middleware('abilities:tasks:write');
    Route::get('/tasks/{todo}', [TodoController::class, 'show'])
        ->middleware('abilities:tasks:read');
    Route::put('/tasks/{todo}', [TodoController::class, 'update'])
        ->middleware('abilities:tasks:write');
    Route::delete('/tasks/{todo}', [TodoController::class, 'destroy'])
        ->middleware('abilities:tasks:write');
    Route::post('/tasks/{todo}/complete', [TodoController::class, 'complete'])
        ->middleware('abilities:tasks:write');
    Route::post('/tasks/{todo}/uncomplete', [TodoController::class, 'uncomplete'])
        ->middleware('abilities:tasks:write');

    // Comments
    Route::get('/tasks/{todo}/comments', [CommentController::class, 'index'])
        ->middleware('abilities:tasks:read');
    Route::post('/tasks/{todo}/comments', [CommentController::class, 'store'])
        ->middleware('abilities:tasks:write');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])
        ->middleware('abilities:tasks:write');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('abilities:tasks:write');

    // Checklists
    Route::get('/tasks/{todo}/checklists', [ChecklistController::class, 'index'])
        ->middleware('abilities:tasks:read');
    Route::post('/tasks/{todo}/checklists', [ChecklistController::class, 'store'])
        ->middleware('abilities:tasks:write');
    Route::post('/checklists/{checklist}/items', [ChecklistController::class, 'storeItem'])
        ->middleware('abilities:tasks:write');
    Route::patch('/checklist-items/{item}/toggle', [ChecklistController::class, 'toggleItem'])
        ->middleware('abilities:tasks:write');

    // Labels
    Route::get('/workspaces/{workspace}/labels', [LabelController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/labels', [LabelController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/labels/{label}', [LabelController::class, 'update'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/labels/{label}', [LabelController::class, 'destroy'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::put('/labels/{label}', [LabelController::class, 'legacyUpdate'])
        ->middleware('abilities:workspaces:write');
    Route::delete('/labels/{label}', [LabelController::class, 'legacyDestroy'])
        ->middleware('abilities:workspaces:write');

    // Tags
    Route::get('/workspaces/{workspace}/tags', [TagController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/tags', [TagController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/tags/{tag}', [TagController::class, 'update'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/tags/{tag}', [TagController::class, 'destroy'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::put('/tags/{tag}', [TagController::class, 'legacyUpdate'])
        ->middleware('abilities:workspaces:write');
    Route::delete('/tags/{tag}', [TagController::class, 'legacyDestroy'])
        ->middleware('abilities:workspaces:write');

    // Task statuses and priorities
    Route::get('/workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/task-statuses', [TaskStatusController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/task-statuses/reorder', [TaskStatusController::class, 'reorder'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'update'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::patch('/workspaces/{workspace}/task-statuses/{taskStatus}/manage', [TaskStatusController::class, 'manage'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/task-statuses/{taskStatus}', [TaskStatusController::class, 'destroy'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();

    Route::get('/workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces/{workspace}/task-priorities', [TaskPriorityController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/task-priorities/reorder', [TaskPriorityController::class, 'reorder'])
        ->middleware('abilities:workspaces:write');
    Route::put('/workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'update'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::patch('/workspaces/{workspace}/task-priorities/{taskPriority}/manage', [TaskPriorityController::class, 'manage'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/task-priorities/{taskPriority}', [TaskPriorityController::class, 'destroy'])
        ->middleware('abilities:workspaces:write')
        ->scopeBindings();

    // Reminders
    Route::get('/tasks/{todo}/reminders', [ReminderController::class, 'index'])
        ->middleware('abilities:tasks:read');
    Route::post('/tasks/{todo}/reminders', [ReminderController::class, 'store'])
        ->middleware('abilities:tasks:write');
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])
        ->middleware('abilities:tasks:write');

    // Attachments
    Route::get('/tasks/{todo}/attachments', [AttachmentController::class, 'index'])
        ->middleware('abilities:tasks:read');
    Route::post('/tasks/{todo}/attachments', [AttachmentController::class, 'store'])
        ->middleware('abilities:tasks:write');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->middleware('abilities:tasks:write');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('abilities:tasks:read');
});
