<?php

use App\Enums\WorkspaceRole;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

test('api resource families enforce explicit read and write abilities', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    $todo = Todo::factory()->for($workspace)->pending()->create();

    $workspaceRead = $user->createToken('workspace-read', ['workspaces:read'])->plainTextToken;
    $this->withToken($workspaceRead)->getJson('/api/workspaces')->assertOk();
    $this->withToken($workspaceRead)
        ->postJson('/api/workspaces', ['name' => 'Blocked'])
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $tasksRead = $user->createToken('tasks-read', ['tasks:read'])->plainTextToken;
    $this->withToken($tasksRead)
        ->getJson("/api/workspaces/{$workspace->id}/tasks")
        ->assertOk();
    $this->withToken($tasksRead)
        ->postJson("/api/tasks/{$todo->id}/complete")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $tasksWrite = $user->createToken('tasks-write', ['tasks:write'])->plainTextToken;
    $this->withToken($tasksWrite)
        ->getJson("/api/tasks/{$todo->id}")
        ->assertForbidden();
    $this->withToken($tasksWrite)
        ->postJson("/api/tasks/{$todo->id}/complete")
        ->assertOk();

    $this->app->make('auth')->forgetGuards();
    $projectsRead = $user->createToken('projects-read', ['projects:read'])->plainTextToken;
    $this->withToken($projectsRead)
        ->getJson("/api/workspaces/{$workspace->id}/projects")
        ->assertOk();
    $this->withToken($projectsRead)
        ->postJson("/api/workspaces/{$workspace->id}/projects", ['name' => 'Blocked'])
        ->assertForbidden();
});
