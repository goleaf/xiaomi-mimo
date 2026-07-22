<?php

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAuthenticatedTodoWithWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    return [$user, $workspace, $todo];
}

test('user can create comment on todo', function () {
    [$user, $workspace, $todo] = createAuthenticatedTodoWithWorkspace();

    $response = $this->actingAs($user)->postJson(route('comments.store', $todo->id), [
        'body' => 'This is a comment',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', ['todo_id' => $todo->id, 'body' => 'This is a comment', 'user_id' => $user->id]);
});

test('inertia comment mutations redirect back so task relations refresh', function () {
    [$user, $workspace, $todo] = createAuthenticatedTodoWithWorkspace();
    $taskUrl = route('todos.show', $todo);

    $this->actingAs($user)
        ->from($taskUrl)
        ->post(route('comments.store', $todo), ['body' => 'Inline comment'])
        ->assertRedirect($taskUrl);

    $this->assertDatabaseHas('comments', [
        'todo_id' => $todo->id,
        'body' => 'Inline comment',
        'user_id' => $user->id,
    ]);
});

test('user can update own comment', function () {
    [$user, $workspace, $todo] = createAuthenticatedTodoWithWorkspace();
    $comment = Comment::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson(route('comments.update', $comment->id), [
        'body' => 'Updated comment',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Updated comment']);
});

test('user can delete own comment', function () {
    [$user, $workspace, $todo] = createAuthenticatedTodoWithWorkspace();
    $comment = Comment::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('comments.destroy', $comment->id));

    $response->assertRedirect();
    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

test('non-owner member cannot update other users comment', function () {
    [$user, $workspace, $todo] = createAuthenticatedTodoWithWorkspace();
    $member = User::factory()->create();
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'member']);
    $otherComment = Comment::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->actingAs($member)->putJson(route('comments.update', $otherComment->id), [
        'body' => 'Hacked!',
    ]);

    $response->assertForbidden();
});
