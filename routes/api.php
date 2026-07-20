<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\TagController;
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
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
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
    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index']);
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // Todos
    Route::get('/workspaces/{workspace}/tasks', [TodoController::class, 'index']);
    Route::post('/workspaces/{workspace}/tasks', [TodoController::class, 'store']);
    Route::get('/tasks/{todo}', [TodoController::class, 'show']);
    Route::put('/tasks/{todo}', [TodoController::class, 'update']);
    Route::delete('/tasks/{todo}', [TodoController::class, 'destroy']);
    Route::post('/tasks/{todo}/complete', [TodoController::class, 'complete']);
    Route::post('/tasks/{todo}/uncomplete', [TodoController::class, 'uncomplete']);

    // Comments
    Route::get('/tasks/{todo}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{todo}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Checklists
    Route::get('/tasks/{todo}/checklists', [ChecklistController::class, 'index']);
    Route::post('/tasks/{todo}/checklists', [ChecklistController::class, 'store']);
    Route::post('/checklists/{checklist}/items', [ChecklistController::class, 'storeItem']);
    Route::patch('/checklist-items/{item}/toggle', [ChecklistController::class, 'toggleItem']);

    // Labels
    Route::get('/workspaces/{workspace}/labels', [LabelController::class, 'index']);
    Route::post('/workspaces/{workspace}/labels', [LabelController::class, 'store']);
    Route::put('/labels/{label}', [LabelController::class, 'update']);
    Route::delete('/labels/{label}', [LabelController::class, 'destroy']);

    // Tags
    Route::get('/workspaces/{workspace}/tags', [TagController::class, 'index']);
    Route::post('/workspaces/{workspace}/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);

    // Reminders
    Route::get('/tasks/{todo}/reminders', [ReminderController::class, 'index']);
    Route::post('/tasks/{todo}/reminders', [ReminderController::class, 'store']);
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy']);

    // Attachments
    Route::get('/tasks/{todo}/attachments', [AttachmentController::class, 'index']);
    Route::post('/tasks/{todo}/attachments', [AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
});
