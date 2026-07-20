<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

/**
 * @param  list<string>  $abilities
 * @return array{user: User, token: string, workspace: Workspace}
 */
function createApiTaskDefinitionActor(array $abilities): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    $token = $user->createToken('task-definition-api', $abilities)->plainTextToken;

    return compact('user', 'token', 'workspace');
}

test('task definition api requires explicit workspace abilities', function () {
    ['user' => $user, 'token' => $unrelatedToken, 'workspace' => $workspace] = createApiTaskDefinitionActor(['tasks:read']);

    $this->withToken($unrelatedToken)
        ->getJson("/api/workspaces/{$workspace->id}/task-statuses")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $readToken = $user->createToken('definitions-read', ['workspaces:read'])->plainTextToken;

    $this->withToken($readToken)
        ->getJson("/api/workspaces/{$workspace->id}/task-statuses")
        ->assertOk()
        ->assertJsonCount(3, 'data');
    $this->withToken($readToken)
        ->postJson("/api/workspaces/{$workspace->id}/task-statuses", [
            'name' => 'Blocked',
            'color' => '#ef4444',
        ])
        ->assertForbidden();
});

test('task definition api exposes status and priority crud', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiTaskDefinitionActor([
        'workspaces:read',
        'workspaces:write',
    ]);

    $statusId = $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/task-statuses", [
            'name' => 'Needs approval',
            'color' => '#8b5cf6',
            'is_completed' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('status.key', 'needs_approval')
        ->json('status.id');

    $this->withToken($token)
        ->putJson("/api/workspaces/{$workspace->id}/task-statuses/{$statusId}", [
            'name' => 'Approved',
            'color' => '#7c3aed',
            'is_completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('status.is_completed', true);
    $this->withToken($token)
        ->patchJson("/api/workspaces/{$workspace->id}/task-statuses/{$statusId}/manage", [
            'operation' => 'set_completion_target',
        ])
        ->assertOk()
        ->assertJsonPath('status.is_completion_target', true);

    $priorityId = $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/task-priorities", [
            'name' => 'Strategic',
            'color' => '#0ea5e9',
        ])
        ->assertCreated()
        ->assertJsonPath('priority.key', 'strategic')
        ->json('priority.id');

    $this->withToken($token)
        ->getJson("/api/workspaces/{$workspace->id}/task-priorities")
        ->assertOk()
        ->assertJsonPath('data.5.id', $priorityId)
        ->assertJsonPath('data.5.permissions.update', true);

    $this->withToken($token)
        ->deleteJson("/api/workspaces/{$workspace->id}/task-priorities/{$priorityId}")
        ->assertNoContent();
});

test('task definition api rejects foreign nested identifiers', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiTaskDefinitionActor([
        'workspaces:read',
        'workspaces:write',
    ]);
    ['workspace' => $foreignWorkspace] = createApiTaskDefinitionActor(['workspaces:write']);
    $foreignStatus = $foreignWorkspace->taskStatuses()->firstOrFail();

    $this->withToken($token)
        ->putJson("/api/workspaces/{$workspace->id}/task-statuses/{$foreignStatus->id}", [
            'name' => 'Leaked',
            'color' => '#000000',
        ])
        ->assertNotFound();

    expect($foreignStatus->refresh()->name)->not->toBe('Leaked');
});
