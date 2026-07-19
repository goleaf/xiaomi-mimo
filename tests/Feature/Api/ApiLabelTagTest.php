<?php

use App\Models\Label;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiLabelUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    return [$user, $token, $workspace];
}

test('API user can list labels', function () {
    [$user, $token, $workspace] = createApiLabelUser();
    Label::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/workspaces/{$workspace->id}/labels");

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('API user can create label', function () {
    [$user, $token, $workspace] = createApiLabelUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/workspaces/{$workspace->id}/labels", [
            'name' => 'Bug',
            'color' => '#ef4444',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('labels', ['name' => 'Bug', 'workspace_id' => $workspace->id]);
});

test('API user can delete label', function () {
    [$user, $token, $workspace] = createApiLabelUser();
    $label = Label::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/labels/{$label->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('labels', ['id' => $label->id]);
});

test('API user can list tags', function () {
    [$user, $token, $workspace] = createApiLabelUser();
    Tag::factory()->count(2)->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/workspaces/{$workspace->id}/tags");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('API user can create tag', function () {
    [$user, $token, $workspace] = createApiLabelUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/workspaces/{$workspace->id}/tags", ['name' => 'urgent']);

    $response->assertCreated();
    $this->assertDatabaseHas('tags', ['name' => 'urgent', 'workspace_id' => $workspace->id]);
});

test('API user can delete tag', function () {
    [$user, $token, $workspace] = createApiLabelUser();
    $tag = Tag::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/tags/{$tag->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
