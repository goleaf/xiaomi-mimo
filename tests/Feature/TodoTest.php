<?php

use App\Models\Label;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createAuthenticatedWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);

    return [$user, $workspace];
}

test('user can create todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();

    $response = $this->actingAs($user)->postJson(route('todos.store', $workspace->id), [
        'title' => 'Test Todo',
        'priority' => 'high',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('todos', ['title' => 'Test Todo', 'workspace_id' => $workspace->id]);
});

test('user can update todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->putJson(route('todos.update', $todo->id), [
        'title' => 'Updated Title',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'Updated Title']);
});

test('task detail form update redirects back to the task page', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->from(route('todos.show', $todo))
        ->put(route('todos.update', $todo), [
            'title' => 'Updated from task detail',
        ]);

    $response->assertRedirect(route('todos.show', $todo));
    expect($todo->refresh()->title)->toBe('Updated from task detail');
});

test('user can clear optional task fields', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create([
        'workspace_id' => $workspace->id,
        'description' => 'Remove this description',
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($user)
        ->putJson(route('todos.update', $todo), [
            'description' => null,
            'due_date' => null,
        ])
        ->assertOk();

    $todo->refresh();

    expect($todo->description)->toBeNull()
        ->and($todo->due_date)->toBeNull();
});

test('task detail page provides localized editing labels', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->where('labels.editTask', 'Edit task')
            ->where('labels.saveChanges', 'Save changes')
        );
});

test('user can complete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->pending()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->postJson(route('todos.complete', $todo->id));

    $response->assertOk();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'status' => 'completed']);
});

test('user can uncomplete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->completed()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->postJson(route('todos.uncomplete', $todo->id));

    $response->assertOk();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'status' => 'pending']);
});

test('user can delete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->deleteJson(route('todos.destroy', $todo->id));

    $response->assertNoContent();
    $this->assertSoftDeleted('todos', ['id' => $todo->id]);
});

test('user can pin and unpin todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id, 'is_pinned' => false]);

    $this->actingAs($user)->postJson(route('todos.pin', $todo->id));
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'is_pinned' => true]);

    $this->actingAs($user)->postJson(route('todos.pin', $todo->id));
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'is_pinned' => false]);
});

test('user can bulk complete todos', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todos = Todo::factory()->count(3)->pending()->create(['workspace_id' => $workspace->id]);
    $ids = $todos->pluck('id')->toArray();

    $response = $this->actingAs($user)->postJson(route('todos.bulk', $workspace->id), [
        'ids' => $ids,
        'action' => 'complete',
    ]);

    $response->assertNoContent();
    $this->assertDatabaseHas('todos', ['status' => 'completed']);
});

test('user can create todo with labels', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $label = Label::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->postJson(route('todos.store', $workspace->id), [
        'title' => 'Todo with label',
        'label_ids' => [$label->id],
    ]);

    $response->assertCreated();
    $todo = Todo::where('title', 'Todo with label')->first();
    $this->assertTrue($todo->labels->contains($label->id));
});

test('todo list filters by status', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    Todo::factory()->count(2)->pending()->create(['workspace_id' => $workspace->id]);
    Todo::factory()->count(3)->completed()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->getJson(route('todos.index', ['workspace' => $workspace->id, 'status' => 'completed']));

    $response->assertOk();
});

test('task index exposes only the implemented list view', function () {
    $taskIndex = file_get_contents(resource_path('js/pages/tasks/Index.vue'));

    expect($taskIndex)
        ->not->toContain("viewMode = ref<'list' | 'board'>")
        ->not->toContain("viewMode = 'board'")
        ->not->toContain('<LayoutGrid')
        ->not->toContain('v-if="viewMode === \'list\'"');
});
