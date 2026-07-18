<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('user can create project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    $response = $this->actingAs($user)->postJson(route('projects.store', $workspace->id), [
        'name' => 'My Project',
        'color' => '#6366f1',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('projects', ['name' => 'My Project']);
});

test('user can archive and restore project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)->postJson(route('projects.archive', [$workspace->id, $project->id]));
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_archived' => true]);

    $this->actingAs($user)->postJson(route('projects.restore', [$workspace->id, $project->id]));
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_archived' => false]);
});

test('user can duplicate project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Original']);

    $response = $this->actingAs($user)->postJson(route('projects.duplicate', [$workspace->id, $project->id]));

    $response->assertCreated();
    $this->assertDatabaseHas('projects', ['name' => 'Original (Copy)', 'workspace_id' => $workspace->id]);
});
