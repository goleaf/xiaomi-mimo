<?php

use App\Enums\ReminderStatus;
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

    $response->assertCreated()
        ->assertJsonPath('reminder.status', ReminderStatus::Pending->value)
        ->assertJsonPath('reminder.attempts', 0);
    $this->assertDatabaseHas('reminders', ['todo_id' => $todo->id]);
});

test('cancelled reminders are retained for audit but excluded from task lists', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();
    $cancelled = Reminder::factory()->for($todo)->for($user)->create([
        'status' => ReminderStatus::Cancelled,
        'cancelled_at' => now(),
    ]);
    $pending = Reminder::factory()->for($todo)->for($user)->create();

    $this->withToken($token)
        ->getJson("/api/tasks/{$todo->id}/reminders")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    expect($cancelled->fresh())->not->toBeNull();
});

test('a delivered reminder cannot be cancelled after delivery', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();
    $reminder = Reminder::factory()->for($todo)->for($user)->create([
        'is_sent' => true,
    ]);

    $this->withToken($token)
        ->deleteJson("/api/reminders/{$reminder->id}")
        ->assertConflict();

    expect($reminder->fresh()?->status)->toBe(ReminderStatus::Delivered);
});

test('API user can delete reminder', function () {
    [$user, $token, $workspace, $todo] = createApiReminderUser();
    $reminder = Reminder::factory()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reminders/{$reminder->id}");

    $response->assertNoContent();
    $this->assertDatabaseHas('reminders', [
        'id' => $reminder->id,
        'status' => 'cancelled',
    ]);
});

test('reminder API rejects creation for another workspace task', function () {
    [$user, $token] = createApiReminderUser();
    [$foreignUser, $foreignToken, $foreignWorkspace, $foreignTodo] = createApiReminderUser();

    $this->withToken($token)
        ->postJson("/api/tasks/{$foreignTodo->id}/reminders", [
            'reminded_at' => now()->addHour()->toIso8601String(),
            'type' => 'in_app',
        ])
        ->assertForbidden();

    expect($foreignTodo->reminders)->toBeEmpty();
});
