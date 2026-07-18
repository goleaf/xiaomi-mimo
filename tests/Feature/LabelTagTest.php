<?php

use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAuthWorkspaceForLabel(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    return [$user, $workspace];
}

test('user can create label', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();

    $response = $this->actingAs($user)->postJson(route('labels.store', $workspace->id), [
        'name' => 'Bug',
        'color' => '#ef4444',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('labels', ['workspace_id' => $workspace->id, 'name' => 'Bug', 'color' => '#ef4444']);
});

test('user can update label', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    $label = Label::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->putJson(route('labels.update', $label->id), [
        'name' => 'Updated Label',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('labels', ['id' => $label->id, 'name' => 'Updated Label']);
});

test('user can delete label', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    $label = Label::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->deleteJson(route('labels.destroy', $label->id));

    $response->assertNoContent();
    $this->assertDatabaseMissing('labels', ['id' => $label->id]);
});

test('user can attach and detach label from todo', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    $label = Label::factory()->create(['workspace_id' => $workspace->id]);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)->postJson(route('labels.attach', $todo->id), ['label_id' => $label->id]);
    $this->assertTrue($todo->fresh()->labels->contains($label->id));

    $this->actingAs($user)->deleteJson(route('labels.detach', [$todo->id, $label->id]));
    $this->assertFalse($todo->fresh()->labels->contains($label->id));
});

test('user can create tag', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();

    $response = $this->actingAs($user)->postJson(route('tags.store', $workspace->id), [
        'name' => 'urgent-fix',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('tags', ['workspace_id' => $workspace->id, 'name' => 'urgent-fix']);
});

test('user can attach and detach tag from todo', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    $tag = Tag::factory()->create(['workspace_id' => $workspace->id]);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)->postJson(route('tags.attach', $todo->id), ['tag_id' => $tag->id]);
    $this->assertTrue($todo->fresh()->tags->contains($tag->id));

    $this->actingAs($user)->deleteJson(route('tags.detach', [$todo->id, $tag->id]));
    $this->assertFalse($todo->fresh()->tags->contains($tag->id));
});

test('user can list labels for workspace', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    Label::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->getJson(route('labels.index', $workspace->id));

    $response->assertOk();
});

test('user can list tags for workspace', function () {
    [$user, $workspace] = createAuthWorkspaceForLabel();
    Tag::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->getJson(route('tags.index', $workspace->id));

    $response->assertOk();
});
