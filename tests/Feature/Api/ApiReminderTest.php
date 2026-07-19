<?php

use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiReminderUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    return [$user, $token, $workspace, $todo];
}

test('API user can list reminders for todo', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();
    Reminder::factory()->count(2)->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/tasks/{$todo->id}/reminders");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('API user can create reminder', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/tasks/{$todo->id}/reminders", [
            'reminded_at' => now()->addHour()->toIso8601String(),
            'type' => 'in_app',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('reminders', ['todo_id' => $todo->id]);
});

test('API user can delete reminder', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();
    $reminder = Reminder::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reminders/{$reminder->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
});
