<?php

use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiAuthUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    return [$user, $token, $workspace];
}

test('API user can list todos', function () {
    [$user, $token, $workspace] = createApiAuthUser();
    Todo::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/workspaces/{$workspace->id}/tasks");

    $response->assertOk()->assertJsonStructure(['data' => [['id', 'title']]]);
    $this->assertCount(3, $response->json('data'));
});

test('API user can create todo', function () {
    [$user, $token, $workspace] = createApiAuthUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/workspaces/{$workspace->id}/tasks", [
            'title' => 'API Todo',
            'priority' => 'high',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('todos', ['title' => 'API Todo', 'workspace_id' => $workspace->id]);
});

test('API user can complete todo', function () {
    [$user, $token, $workspace] = createApiAuthUser();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id, 'status' => 'pending']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/tasks/{$todo->id}/complete");

    $response->assertOk();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'status' => 'completed']);
});

test('API user can delete todo', function () {
    [$user, $token, $workspace] = createApiAuthUser();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/tasks/{$todo->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('todos', ['id' => $todo->id]);
});

test('API unauthenticated cannot list todos', function () {
    $workspace = Workspace::factory()->create();
    $response = $this->getJson("/api/workspaces/{$workspace->id}/tasks");
    $response->assertUnauthorized();
});
