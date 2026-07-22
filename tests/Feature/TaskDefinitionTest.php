<?php

use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

test('new workspaces receive canonical task definitions', function () {
    $workspace = Workspace::factory()->create();

    expect($workspace->taskStatuses()->ordered()->pluck('key')->all())
        ->toBe(['pending', 'in_progress', 'completed'])
        ->and($workspace->taskPriorities()->ordered()->pluck('key')->all())
        ->toBe(['none', 'low', 'medium', 'high', 'urgent'])
        ->and($workspace->taskStatuses()->where('is_default', true)->value('key'))
        ->toBe('pending')
        ->and($workspace->taskStatuses()->where('is_completion_target', true)->value('key'))
        ->toBe('completed')
        ->and($workspace->taskPriorities()->where('is_default', true)->value('key'))
        ->toBe('none');
});

test('active task definition keys validate inside their workspace', function () {
    $workspace = Workspace::factory()->create();

    $validator = Validator::make(['priority' => 'high'], [
        'priority' => [
            Rule::exists('task_priorities', 'key')
                ->where('workspace_id', $workspace->id)
                ->where('is_archived', 0),
        ],
    ]);

    expect($workspace->taskPriorities()->where('key', 'high')->where('is_archived', false)->count())
        ->toBe(1)
        ->and($validator->passes())->toBeTrue();
});

test('workspace task definition defaults are unique', function () {
    $workspace = Workspace::factory()->create();

    expect(fn () => TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_default' => true,
    ]))->toThrow(QueryException::class)
        ->and(fn () => TaskStatus::factory()->create([
            'workspace_id' => $workspace->id,
            'is_completed' => true,
            'is_completion_target' => true,
        ]))->toThrow(QueryException::class)
        ->and(fn () => TaskPriority::factory()->create([
            'workspace_id' => $workspace->id,
            'is_default' => true,
        ]))->toThrow(QueryException::class);
});

test('database rejects contradictory task definition semantics', function () {
    $workspace = Workspace::factory()->create();
    $workspace->taskStatuses()->where('is_default', true)->update(['is_default' => false]);
    $workspace->taskStatuses()->where('is_completion_target', true)->update(['is_completion_target' => false]);
    $workspace->taskPriorities()->where('is_default', true)->update(['is_default' => false]);

    expect(fn () => TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_completed' => false,
        'is_completion_target' => true,
    ]))->toThrow(QueryException::class)
        ->and(fn () => TaskStatus::factory()->create([
            'workspace_id' => $workspace->id,
            'is_default' => true,
            'is_completed' => true,
        ]))->toThrow(QueryException::class)
        ->and(fn () => TaskPriority::factory()->create([
            'workspace_id' => $workspace->id,
            'is_default' => true,
            'is_archived' => true,
        ]))->toThrow(QueryException::class);
});

test('task definition foreign keys reject another workspace', function () {
    $taskWorkspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $foreignStatus = $otherWorkspace->taskStatuses()->where('key', 'pending')->firstOrFail();
    $priority = $taskWorkspace->taskPriorities()->where('key', 'none')->firstOrFail();

    expect(fn () => Todo::factory()->create([
        'workspace_id' => $taskWorkspace->id,
        'status' => $foreignStatus->key,
        'status_id' => $foreignStatus->id,
        'priority' => $priority->key,
        'priority_id' => $priority->id,
    ]))->toThrow(QueryException::class);
});

test('database requires synchronized task definition ids and keys', function () {
    $workspace = Workspace::factory()->create();
    $status = $workspace->taskStatuses()->where('key', 'pending')->firstOrFail();
    $priority = $workspace->taskPriorities()->where('key', 'none')->firstOrFail();

    expect(fn () => DB::table('todos')->insert([
        'id' => (string) Str::uuid(),
        'workspace_id' => $workspace->id,
        'title' => 'Missing status definition',
        'status' => $status->key,
        'status_id' => null,
        'priority' => $priority->key,
        'priority_id' => $priority->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    $todo = Todo::factory()->for($workspace)->pending()->create();

    expect(fn () => DB::table('todos')
        ->where('id', $todo->id)
        ->update(['status' => 'completed']))
        ->toThrow(QueryException::class);
});

test('workspace owner can fully manage task statuses', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $defaultStatus = $workspace->taskStatuses()->where('is_default', true)->firstOrFail();

    $this->actingAs($owner)->putJson(route('task-statuses.update', [$workspace, $defaultStatus]), [
        'name' => $defaultStatus->name,
        'color' => $defaultStatus->color,
        'is_completed' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('is_completed');

    $create = $this->actingAs($owner)->postJson(route('task-statuses.store', $workspace), [
        'name' => 'Needs review',
        'color' => '#8b5cf6',
        'is_completed' => false,
    ]);

    $create->assertCreated()
        ->assertJsonPath('status.name', 'Needs review')
        ->assertJsonPath('status.key', 'needs_review');
    $status = $workspace->taskStatuses()->where('key', 'needs_review')->firstOrFail();

    $this->putJson(route('task-statuses.update', [$workspace, $status]), [
        'name' => 'Reviewed',
        'color' => '#7c3aed',
        'is_completed' => false,
    ])->assertOk()->assertJsonPath('status.name', 'Reviewed');

    $this->patchJson(route('task-statuses.manage', [$workspace, $status]), [
        'operation' => 'set_default',
    ])->assertOk()->assertJsonPath('status.is_default', true);

    $ids = $workspace->taskStatuses()->ordered()->pluck('id')->reverse()->values()->all();
    $this->putJson(route('task-statuses.reorder', $workspace), ['ids' => $ids])->assertNoContent();

    expect($workspace->taskStatuses()->ordered()->pluck('id')->all())->toBe($ids);

    $replacement = $workspace->taskStatuses()->where('key', 'pending')->firstOrFail();
    $this->deleteJson(route('task-statuses.destroy', [$workspace, $status]), [
        'replacement_id' => $replacement->id,
    ])->assertNoContent();

    expect($status->fresh())->toBeNull()
        ->and($replacement->fresh()?->is_default)->toBeTrue();
});

test('workspace owner can fully manage task priorities', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

    $create = $this->actingAs($owner)->postJson(route('task-priorities.store', $workspace), [
        'name' => 'Critical path',
        'color' => '#dc2626',
    ]);

    $create->assertCreated()
        ->assertJsonPath('priority.name', 'Critical path')
        ->assertJsonPath('priority.key', 'critical_path');
    $priority = $workspace->taskPriorities()->where('key', 'critical_path')->firstOrFail();

    $this->patchJson(route('task-priorities.manage', [$workspace, $priority]), [
        'operation' => 'set_default',
    ])->assertOk()->assertJsonPath('priority.is_default', true);

    $replacement = $workspace->taskPriorities()->where('key', 'none')->firstOrFail();
    $this->deleteJson(route('task-priorities.destroy', [$workspace, $priority]), [
        'replacement_id' => $replacement->id,
    ])->assertNoContent();

    expect($replacement->fresh()?->is_default)->toBeTrue();
});

test('task definition writes are limited to workspace administrators and scoped records', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $otherWorkspace = Workspace::factory()->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => 'member',
    ]);

    $this->actingAs($member)
        ->getJson(route('task-statuses.index', $workspace))
        ->assertOk()
        ->assertJsonCount(3, 'statuses');

    $this->postJson(route('task-statuses.store', $workspace), [
        'name' => 'Blocked',
        'color' => '#ef4444',
    ])->assertForbidden();

    $foreignStatus = $otherWorkspace->taskStatuses()->firstOrFail();
    $this->actingAs($owner)
        ->putJson(route('task-statuses.update', [$workspace, $foreignStatus]), [
            'name' => 'Changed',
            'color' => '#000000',
        ])->assertNotFound();
});

test('deleting used definitions replaces task keys and completion state atomically', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $inProgress = $workspace->taskStatuses()->where('key', 'in_progress')->firstOrFail();
    $completed = $workspace->taskStatuses()->where('key', 'completed')->firstOrFail();
    $priority = $workspace->taskPriorities()->where('key', 'none')->firstOrFail();
    $todo = Todo::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => 'in_progress',
        'status_id' => $inProgress->id,
        'priority' => 'none',
        'priority_id' => $priority->id,
        'completed_at' => null,
    ]);

    $this->actingAs($owner)->deleteJson(route('task-statuses.destroy', [$workspace, $inProgress]), [
        'replacement_id' => $completed->id,
    ])->assertNoContent();

    expect($todo->fresh())
        ->status->value->toBe('completed')
        ->status_id->toBe($completed->id)
        ->completed_at->not->toBeNull();
});

test('replacing one completed status with another preserves completion timestamps', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $source = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'verified',
        'name' => 'Verified',
        'is_completed' => true,
    ]);
    $target = $workspace->taskStatuses()->where('key', 'completed')->firstOrFail();
    $priority = $workspace->taskPriorities()->where('key', 'none')->firstOrFail();
    $completedAt = now()->subDays(5)->startOfSecond();
    $todo = Todo::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => $source->key,
        'status_id' => $source->id,
        'priority' => $priority->key,
        'priority_id' => $priority->id,
        'completed_at' => $completedAt,
    ]);

    $this->actingAs($owner)->deleteJson(route('task-statuses.destroy', [$workspace, $source]), [
        'replacement_id' => $target->id,
    ])->assertNoContent();

    expect($todo->fresh()?->completed_at?->equalTo($completedAt))->toBeTrue();
});

test('soft deleted tasks remain visible to definition replacement safeguards', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $status = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'qa_review',
        'name' => 'QA review',
    ]);
    $priority = TaskPriority::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'strategic',
        'name' => 'Strategic',
    ]);
    $todo = Todo::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => $status->key,
        'status_id' => $status->id,
        'priority' => $priority->key,
        'priority_id' => $priority->id,
    ]);
    $todo->delete();

    $statuses = $this->actingAs($owner)
        ->getJson(route('task-statuses.index', $workspace))
        ->assertOk()
        ->json('statuses');

    expect(collect($statuses)->firstWhere('id', $status->id)['todos_count'] ?? null)->toBe(1);

    $this->deleteJson(route('task-statuses.destroy', [$workspace, $status]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('replacement_id');

    $replacementStatus = $workspace->taskStatuses()->where('key', 'pending')->firstOrFail();
    $replacementPriority = $workspace->taskPriorities()->where('key', 'none')->firstOrFail();
    $this->deleteJson(route('task-statuses.destroy', [$workspace, $status]), [
        'replacement_id' => $replacementStatus->id,
    ])->assertNoContent();
    $this->deleteJson(route('task-priorities.destroy', [$workspace, $priority]), [
        'replacement_id' => $replacementPriority->id,
    ])->assertNoContent();

    $replacedTodo = Todo::withTrashed()->findOrFail($todo->id);

    expect($replacedTodo->status_id)->toBe($replacementStatus->id)
        ->and($replacedTodo->priority_id)->toBe($replacementPriority->id);
});

test('tasks accept workspace status and priority keys or ids with completion semantics', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $status = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'verified',
        'name' => 'Verified',
        'is_completed' => true,
    ]);
    $priority = TaskPriority::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'strategic',
        'name' => 'Strategic',
    ]);

    $todoId = $this->actingAs($owner)
        ->postJson(route('todos.store', $workspace), [
            'title' => 'Dynamic task',
            'status' => 'verified',
            'priority_id' => $priority->id,
        ])
        ->assertCreated()
        ->assertJsonPath('todo.status', 'verified')
        ->assertJsonPath('todo.status_id', $status->id)
        ->assertJsonPath('todo.priority', 'strategic')
        ->assertJsonPath('todo.priority_id', $priority->id)
        ->assertJsonPath('todo.is_completed', true)
        ->json('todo.id');

    $todo = Todo::query()->findOrFail($todoId);
    expect($todo->completed_at)->not->toBeNull();

    $openStatus = $workspace->taskStatuses()->where('key', 'in_progress')->firstOrFail();
    $this->putJson(route('api.v1.tasks.update', [$workspace, $todo], false), ['status_id' => $openStatus->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.is_completed', false);

    expect($todo->refresh()->completed_at)->toBeNull();
});

test('tasks reject archived and foreign task definitions atomically', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $otherWorkspace = Workspace::factory()->create();
    $archived = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'archived_status',
        'name' => 'Archived status',
        'is_archived' => true,
    ]);
    $foreignPriority = $otherWorkspace->taskPriorities()->where('key', 'high')->firstOrFail();

    $this->actingAs($owner)
        ->postJson(route('todos.store', $workspace), [
            'title' => 'Rejected task',
            'status_id' => $archived->id,
            'priority_id' => $foreignPriority->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status_id', 'priority_id']);

    $this->assertDatabaseMissing('todos', ['title' => 'Rejected task']);
});
