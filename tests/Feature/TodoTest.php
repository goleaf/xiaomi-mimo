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

    $response->assertRedirect();
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
        ->assertRedirect();

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

test('task detail page provides labels from the task workspace', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);
    Label::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Feature']);
    Label::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bug']);

    $otherUser = User::factory()->create();
    $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);
    Label::factory()->create(['workspace_id' => $otherWorkspace->id, 'name' => 'Private']);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('availableLabels.data', 2)
            ->where('availableLabels.data.0.name', 'Bug')
            ->where('availableLabels.data.1.name', 'Feature')
        );
});

test('user can replace and clear task labels', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);
    $originalLabel = Label::factory()->create(['workspace_id' => $workspace->id]);
    $replacementLabel = Label::factory()->create(['workspace_id' => $workspace->id]);
    $todo->labels()->attach($originalLabel);

    $this->actingAs($user)
        ->putJson(route('todos.update', $todo), [
            'label_ids' => [$replacementLabel->id],
        ])
        ->assertRedirect();

    expect($todo->fresh()->labels->modelKeys())->toBe([$replacementLabel->id]);

    $this->actingAs($user)
        ->putJson(route('todos.update', $todo), [
            'label_ids' => [],
        ])
        ->assertRedirect();

    expect($todo->fresh()->labels)->toBeEmpty();
});

test('task label updates reject labels from another workspace atomically', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);
    $currentLabel = Label::factory()->create(['workspace_id' => $workspace->id]);
    $todo->labels()->attach($currentLabel);

    $otherUser = User::factory()->create();
    $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);
    $foreignLabel = Label::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $this->actingAs($user)
        ->putJson(route('todos.update', $todo), [
            'title' => 'This must not be applied',
            'label_ids' => [$foreignLabel->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label_ids.0');

    $todo->refresh();

    expect($todo->title)->not->toBe('This must not be applied')
        ->and($todo->labels->modelKeys())->toBe([$currentLabel->id]);
});

test('user can complete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->pending()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->postJson(route('todos.complete', $todo->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'status' => 'completed']);
});

test('inertia task mutations redirect back so page props refresh', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->pending()->create(['workspace_id' => $workspace->id]);
    $tasksUrl = route('todos.index', $workspace);

    $this->actingAs($user)
        ->from($tasksUrl)
        ->post(route('todos.complete', $todo))
        ->assertRedirect($tasksUrl);

    expect($todo->refresh()->completed_at)->not->toBeNull();
});

test('user can uncomplete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->completed()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->postJson(route('todos.uncomplete', $todo->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('todos', ['id' => $todo->id, 'status' => 'pending']);
});

test('user can delete todo', function () {
    [$user, $workspace] = createAuthenticatedWorkspace();
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)->deleteJson(route('todos.destroy', $todo->id));

    $response->assertRedirect();
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

    $response->assertRedirect();
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

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('todos.index', ['status' => 'completed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('todos.data', 3)
            ->where('todos.data.0.status', 'completed'));
});

test('task index exposes the implemented list and board views', function () {
    $taskIndex = file_get_contents(resource_path('js/pages/tasks/Index.vue'));
    $filterBar = file_get_contents(resource_path('js/components/task/TaskFilterBar.vue'));

    expect($taskIndex)
        ->toContain('filters.view === \'board\'')
        ->toContain('<BoardView')
        ->toContain('<TaskList')
        ->and($filterBar)
        ->toContain("setView('list')")
        ->toContain("setView('board')");
});
