<?php

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiCommentUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    return [$user, $token, $workspace, $todo];
}

test('API user can list comments for todo', function () {
    [$user, $token, $workspace, $todo] = createApiCommentUser();
    Comment::factory()->count(3)->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/tasks/{$todo->id}/comments");

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('API user can create comment', function () {
    [$user, $token, $workspace, $todo] = createApiCommentUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/tasks/{$todo->id}/comments", ['body' => 'Great work!']);

    $response->assertCreated();
    $this->assertDatabaseHas('comments', ['todo_id' => $todo->id, 'body' => 'Great work!']);
});

test('API user can update comment', function () {
    [$user, $token, $workspace, $todo] = createApiCommentUser();
    $comment = Comment::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/comments/{$comment->id}", ['body' => 'Updated']);

    $response->assertOk();
    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Updated']);
});

test('API user can delete comment', function () {
    [$user, $token, $workspace, $todo] = createApiCommentUser();
    $comment = Comment::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/comments/{$comment->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});
