<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiProjectUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    return [$user, $token, $workspace];
}

test('API user can list projects', function () {
    [$user, $token, $workspace] = createApiProjectUser();
    Project::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/workspaces/{$workspace->id}/projects");

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('API user can create project', function () {
    [$user, $token, $workspace] = createApiProjectUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/workspaces/{$workspace->id}/projects", [
            'name' => 'My API Project',
            'color' => '#10b981',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('projects', ['name' => 'My API Project', 'workspace_id' => $workspace->id]);
});

test('API user can get project', function () {
    [$user, $token, $workspace] = createApiProjectUser();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/projects/{$project->id}");

    $response->assertOk()->assertJsonPath('data.name', $project->name);
});

test('API user can update project', function () {
    [$user, $token, $workspace] = createApiProjectUser();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/projects/{$project->id}", ['name' => 'Updated Project']);

    $response->assertOk();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Project']);
});

test('API user can delete project', function () {
    [$user, $token, $workspace] = createApiProjectUser();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/projects/{$project->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});
