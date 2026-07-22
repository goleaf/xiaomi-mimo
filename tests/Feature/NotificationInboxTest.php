<?php

use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function insertInboxNotification(User $user, array $data = [], ?string $readAt = null, ?string $createdAt = null): string
{
    $id = Str::uuid()->toString();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => ReminderNotification::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode($data),
        'read_at' => $readAt,
        'created_at' => $createdAt ?? now(),
        'updated_at' => $createdAt ?? now(),
    ]);

    return $id;
}

test('notification inbox is user scoped with global totals and direct task links', function () {
    $user = User::factory()->create();
    $foreign = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $todo = Todo::factory()->for($workspace)->create();
    $reminderNotificationId = insertInboxNotification($user, [
        'kind' => 'reminder',
        'todo_id' => $todo->id,
        'todo_title' => $todo->title,
    ]);
    insertInboxNotification($user, ['title' => 'Read'], now()->toDateTimeString());
    insertInboxNotification($foreign, ['title' => 'Foreign']);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 2)
            ->where('stats.total', 2)
            ->where('stats.unread', 1)
            ->where('stats.read', 1)
            ->where('filters.status', 'all')
            ->where('notifications.data', fn ($notifications): bool => $notifications->contains(
                fn (array $notification): bool => $notification['id'] === $reminderNotificationId
                    && $notification['url'] === route('todos.show', $todo),
            )));
});

test('unread filter is server backed and pagination remains stable', function () {
    $user = User::factory()->create();
    $createdAt = '2026-07-22 12:00:00';

    foreach (range(1, 21) as $number) {
        insertInboxNotification($user, ['title' => "Notice {$number}"], createdAt: $createdAt);
    }
    insertInboxNotification($user, ['title' => 'Read'], $createdAt, $createdAt);

    $first = $this->actingAs($user)
        ->get(route('notifications.index', ['status' => 'unread', 'per_page' => 20]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.total', 21)
            ->has('notifications.data', 20)
            ->where('filters.status', 'unread'));

    $firstIds = collect($first->viewData('page')['props']['notifications']['data'])->pluck('id');
    $second = $this->get(route('notifications.index', [
        'status' => 'unread',
        'per_page' => 20,
        'page' => 2,
    ]))->assertOk();
    $secondIds = collect($second->viewData('page')['props']['notifications']['data'])->pluck('id');

    expect($firstIds)->toHaveCount(20)
        ->and($secondIds)->toHaveCount(1)
        ->and($firstIds->intersect($secondIds))->toBeEmpty();
});

test('notification mutations affect only the authenticated users rows', function () {
    $user = User::factory()->create();
    $foreign = User::factory()->create();
    $ownId = insertInboxNotification($user, ['title' => 'Own']);
    $otherOwnId = insertInboxNotification($user, ['title' => 'Own two']);
    $foreignId = insertInboxNotification($foreign, ['title' => 'Foreign']);

    $this->actingAs($user)
        ->post(route('notifications.markRead', ['id' => $foreignId]))
        ->assertRedirect();
    $this->post(route('notifications.markRead', ['id' => $ownId]))->assertRedirect();
    $this->post(route('notifications.markAllRead'))->assertRedirect();

    expect(DB::table('notifications')->where('id', $ownId)->value('read_at'))->not->toBeNull()
        ->and(DB::table('notifications')->where('id', $otherOwnId)->value('read_at'))->not->toBeNull()
        ->and(DB::table('notifications')->where('id', $foreignId)->value('read_at'))->toBeNull();
});

test('notification inbox rejects unsupported filters', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications.index', ['status' => 'foreign', 'per_page' => 500]))
        ->assertSessionHasErrors(['status', 'per_page']);
});
