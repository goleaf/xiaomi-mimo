<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('authenticated user can view workspaces', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    $response = $this->actingAs($user)->get(route('workspaces.index'));

    $response->assertOk();
});

test('authenticated user can create workspace', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('workspaces.store'), [
        'name' => 'Test Workspace',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('workspaces', ['name' => 'Test Workspace']);
    $this->assertDatabaseHas('workspace_members', ['user_id' => $user->id, 'role' => 'owner']);
});

test('workspace owner can update workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    $response = $this->actingAs($user)->putJson(route('workspaces.update', $workspace->id), [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Updated Name']);
});

test('workspace owner can delete workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    $response = $this->actingAs($user)->deleteJson(route('workspaces.destroy', $workspace->id));

    $response->assertNoContent();
    $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
});

test('member cannot delete workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'member']);

    $response = $this->actingAs($member)->deleteJson(route('workspaces.destroy', $workspace->id));

    $response->assertForbidden();
});
