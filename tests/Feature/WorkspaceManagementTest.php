<?php

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * @return array{owner: User, workspace: Workspace}
 */
function createWorkspaceManagementContext(): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    return compact('owner', 'workspace');
}

test('workspace portfolio exposes accurate counts current state and permissions', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $member = User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);
    Project::factory()->count(2)->for($workspace)->create();
    Todo::factory()->count(3)->for($workspace)->create();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Index')
            ->has('workspaces.data', 1)
            ->where('workspaces.data.0.id', $workspace->id)
            ->where('workspaces.data.0.members_count', 2)
            ->where('workspaces.data.0.projects_count', 2)
            ->where('workspaces.data.0.todos_count', 3)
            ->where('workspaces.data.0.is_current', true)
            ->where('workspaces.data.0.permissions.update', true)
            ->where('workspaces.data.0.permissions.delete', true));
});

test('workspace portfolio selects the first authorized workspace when the session has no selection', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();

    $this->actingAs($owner)
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertSessionHas('current_workspace_id', $workspace->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspaces.data.0.is_current', true));
});

test('workspace owner can duplicate its structure without operational data', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $label = Label::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Important',
        'color' => '#f97316',
    ]);
    $tag = Tag::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Launch',
    ]);
    Project::factory()->for($workspace)->create();
    Todo::factory()->for($workspace)->create();

    $response = $this->actingAs($owner)->postJson("/workspaces/{$workspace->id}/duplicate", [
        'name' => 'Operations Copy',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('workspace.name', 'Operations Copy')
        ->assertJsonPath('workspace.description', $workspace->description);

    $copy = Workspace::query()->where('name', 'Operations Copy')->firstOrFail();

    expect($copy->id)->not->toBe($workspace->id)
        ->and($copy->slug)->not->toBe($workspace->slug)
        ->and($copy->labels()->where('name', $label->name)->where('color', $label->color)->exists())->toBeTrue()
        ->and($copy->tags()->where('name', $tag->name)->exists())->toBeTrue()
        ->and($copy->projects()->count())->toBe(0)
        ->and($copy->todos()->count())->toBe(0)
        ->and($copy->memberRole($owner))->toBe(WorkspaceRole::Owner->value);
});

test('workspace member cannot duplicate a workspace', function () {
    ['workspace' => $workspace] = createWorkspaceManagementContext();
    $member = User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);

    $this->actingAs($member)
        ->postJson("/workspaces/{$workspace->id}/duplicate", ['name' => 'Forbidden Copy'])
        ->assertForbidden();

    expect(Workspace::query()->where('name', 'Forbidden Copy')->exists())->toBeFalse();
});

test('deleting the current workspace selects the remaining workspace', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $fallback = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $fallback->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->deleteJson(route('workspaces.destroy', $workspace))
        ->assertNoContent()
        ->assertSessionHas('current_workspace_id', $fallback->id);

    $this->assertModelMissing($workspace);
});

test('deleting the final workspace clears the current workspace session', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->deleteJson(route('workspaces.destroy', $workspace))
        ->assertNoContent()
        ->assertSessionMissing('current_workspace_id');

    $this->assertModelMissing($workspace);
});

test('workspace names cannot be emptied during update', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();

    $this->actingAs($owner)
        ->putJson(route('workspaces.update', $workspace), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($workspace->refresh()->name)->not->toBe('');
});

test('workspace names containing only whitespace are rejected by web and api writes', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $token = $owner->createToken('workspace-management')->plainTextToken;

    $this->actingAs($owner)
        ->putJson(route('workspaces.update', $workspace), ['name' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->withToken($token)
        ->postJson('/api/workspaces', ['name' => "\t \n"])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($workspace->refresh()->name)->not->toBe('')
        ->and(Workspace::query()->count())->toBe(1);
});

test('workspace roles receive only their allowed portfolio actions', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $admin->id,
        'role' => WorkspaceRole::Admin,
    ]);
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);

    expect($admin->can('view', $workspace))->toBeTrue()
        ->and($admin->can('update', $workspace))->toBeTrue()
        ->and($admin->can('duplicate', $workspace))->toBeFalse()
        ->and($admin->can('delete', $workspace))->toBeFalse()
        ->and($member->can('view', $workspace))->toBeTrue()
        ->and($member->can('update', $workspace))->toBeFalse()
        ->and($member->can('duplicate', $workspace))->toBeFalse()
        ->and($member->can('delete', $workspace))->toBeFalse()
        ->and($outsider->can('view', $workspace))->toBeFalse()
        ->and($outsider->can('update', $workspace))->toBeFalse();

    $this->actingAs($member)
        ->postJson(route('workspaces.switch', $workspace))
        ->assertOk()
        ->assertSessionHas('current_workspace_id', $workspace->id);

    $this->actingAs($outsider)
        ->postJson(route('workspaces.switch', $workspace))
        ->assertForbidden();
});

test('workspace owner can clear its description', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();

    expect($workspace->description)->not->toBeNull();

    $this->actingAs($owner)
        ->putJson(route('workspaces.update', $workspace), ['description' => null])
        ->assertOk();

    expect($workspace->refresh()->description)->toBeNull();
});

test('workspace duplication is available through the existing api', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $token = $owner->createToken('workspace-management')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/duplicate", [
            'name' => 'API Workspace Copy',
        ])
        ->assertCreated()
        ->assertJsonPath('workspace.name', 'API Workspace Copy');

    $this->assertDatabaseHas('workspaces', [
        'owner_id' => $owner->id,
        'name' => 'API Workspace Copy',
    ]);
});

test('workspace api writes reject tokens without the workspace write ability', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createWorkspaceManagementContext();
    $token = $owner->createToken('read-only-workspaces', ['workspaces:read'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/workspaces')
        ->assertOk()
        ->assertJsonPath('data.0.permissions.view', true)
        ->assertJsonPath('data.0.permissions.update', false)
        ->assertJsonPath('data.0.permissions.duplicate', false)
        ->assertJsonPath('data.0.permissions.delete', false)
        ->assertJsonPath('data.0.permissions.manage_members', false);

    $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/duplicate", [
            'name' => 'Restricted Copy',
        ])
        ->assertForbidden();

    expect(Workspace::query()->where('name', 'Restricted Copy')->exists())->toBeFalse();
});
