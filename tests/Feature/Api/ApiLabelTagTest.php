<?php

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $abilities
 * @return array{user: User, token: string, workspace: Workspace}
 */
function createApiMetadataActor(array $abilities): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    $token = $user->createToken('metadata-api', $abilities)->plainTextToken;

    return compact('user', 'token', 'workspace');
}

test('metadata api requires explicit read and write abilities', function () {
    ['user' => $user, 'token' => $unrelatedToken, 'workspace' => $workspace] = createApiMetadataActor(['tasks:read']);

    $this->withToken($unrelatedToken)
        ->getJson("/api/workspaces/{$workspace->id}/labels")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $readToken = $user->createToken('metadata-read', ['workspaces:read'])->plainTextToken;

    $this->withToken($readToken)
        ->getJson("/api/workspaces/{$workspace->id}/labels")
        ->assertOk();
    $this->withToken($readToken)
        ->postJson("/api/workspaces/{$workspace->id}/labels", ['name' => 'Blocked', 'color' => '#ef4444'])
        ->assertForbidden();
});

test('metadata api exposes complete canonical label and tag crud', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiMetadataActor([
        'workspaces:read',
        'workspaces:write',
    ]);

    $labelId = $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/labels", [
            'name' => 'API Label',
            'color' => '#ef4444',
        ])
        ->assertCreated()
        ->assertJsonPath('label.todos_count', 0)
        ->json('label.id');

    $this->withToken($token)
        ->putJson("/api/workspaces/{$workspace->id}/labels/{$labelId}", [
            'name' => 'API Label Updated',
            'color' => '#0ea5e9',
        ])
        ->assertOk()
        ->assertJsonPath('label.name', 'API Label Updated');

    $tagId = $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/tags", ['name' => 'api-tag'])
        ->assertCreated()
        ->assertJsonPath('tag.todos_count', 0)
        ->json('tag.id');

    $this->withToken($token)
        ->putJson("/api/workspaces/{$workspace->id}/tags/{$tagId}", ['name' => 'api-tag-updated'])
        ->assertOk()
        ->assertJsonPath('tag.name', 'api-tag-updated');

    $this->withToken($token)
        ->getJson("/api/workspaces/{$workspace->id}/labels")
        ->assertOk()
        ->assertJsonPath('data.0.permissions.update', true);

    $this->withToken($token)
        ->deleteJson("/api/workspaces/{$workspace->id}/labels/{$labelId}")
        ->assertNoContent();
    $this->withToken($token)
        ->deleteJson("/api/workspaces/{$workspace->id}/tags/{$tagId}")
        ->assertNoContent();
});

test('legacy metadata mutation routes remain compatible and ability protected', function () {
    ['user' => $owner, 'token' => $writeToken, 'workspace' => $workspace] = createApiMetadataActor([
        'workspaces:write',
    ]);
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();

    $this->withToken($writeToken)
        ->putJson("/api/labels/{$label->id}", ['name' => 'Legacy Label', 'color' => '#0ea5e9'])
        ->assertOk()
        ->assertJsonPath('label.name', 'Legacy Label');
    $this->withToken($writeToken)
        ->putJson("/api/tags/{$tag->id}", ['name' => 'legacy-tag'])
        ->assertOk()
        ->assertJsonPath('tag.name', 'legacy-tag');

    $this->app->make('auth')->forgetGuards();
    $readToken = $owner->createToken('metadata-legacy-read', ['workspaces:read'])->plainTextToken;
    $this->withToken($readToken)
        ->deleteJson("/api/labels/{$label->id}")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $this->withToken($writeToken)
        ->deleteJson("/api/labels/{$label->id}")
        ->assertNoContent();
    $this->withToken($writeToken)
        ->deleteJson("/api/tags/{$tag->id}")
        ->assertNoContent();
});

test('metadata api rejects foreign nested identifiers without mutation', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiMetadataActor([
        'workspaces:read',
        'workspaces:write',
    ]);
    ['workspace' => $foreignWorkspace] = createApiMetadataActor(['workspaces:write']);
    $foreignLabel = Label::factory()->for($foreignWorkspace)->create();
    $foreignTag = Tag::factory()->for($foreignWorkspace)->create();

    $this->withToken($token)
        ->putJson("/api/workspaces/{$workspace->id}/labels/{$foreignLabel->id}", ['name' => 'Leaked'])
        ->assertNotFound();
    $this->withToken($token)
        ->deleteJson("/api/workspaces/{$workspace->id}/tags/{$foreignTag->id}")
        ->assertNotFound();

    expect($foreignLabel->refresh()->name)->not->toBe('Leaked')
        ->and($foreignTag->fresh())->not->toBeNull();
});

test('metadata resources report live task usage counts', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiMetadataActor(['workspaces:read']);
    $todo = Todo::factory()->for($workspace)->create();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    $todo->labels()->attach($label);
    $todo->tags()->attach($tag);

    $this->withToken($token)
        ->getJson("/api/workspaces/{$workspace->id}/labels")
        ->assertOk()
        ->assertJsonPath('data.0.todos_count', 1);
    $this->withToken($token)
        ->getJson("/api/workspaces/{$workspace->id}/tags")
        ->assertOk()
        ->assertJsonPath('data.0.todos_count', 1);
});

test('metadata api collections and creation are bounded per workspace', function () {
    ['token' => $token, 'workspace' => $workspace] = createApiMetadataActor([
        'workspaces:read',
        'workspaces:write',
    ]);
    foreach (range(0, Label::MAX_PER_WORKSPACE) as $index) {
        $workspace->labels()->create([
            'name' => 'Label '.$index,
            'color' => '#6366f1',
        ]);
    }

    $this->withToken($token)
        ->getJson("/api/workspaces/{$workspace->id}/labels")
        ->assertOk()
        ->assertJsonCount(Label::MAX_PER_WORKSPACE, 'data');
    $this->withToken($token)
        ->postJson("/api/workspaces/{$workspace->id}/labels", [
            'name' => 'Over the limit',
            'color' => '#ef4444',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});
