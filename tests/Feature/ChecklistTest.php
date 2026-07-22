<?php

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAuthenticatedChecklist(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);
    $checklist = Checklist::factory()->create(['todo_id' => $todo->id]);

    return [$user, $workspace, $todo, $checklist];
}

test('user can create checklist', function () {
    [$user, $workspace, $todo] = createAuthenticatedChecklist();

    $response = $this->actingAs($user)->postJson(route('checklists.store', $todo->id), [
        'name' => 'My Checklist',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('checklists', ['todo_id' => $todo->id, 'name' => 'My Checklist']);
});

test('inertia checklist mutations redirect back and reject foreign workspaces', function () {
    [$user, $workspace, $todo] = createAuthenticatedChecklist();
    $tasksUrl = route('todos.index', $workspace);

    $this->actingAs($user)
        ->from($tasksUrl)
        ->post(route('checklists.store', $todo), ['name' => 'Inline checklist'])
        ->assertRedirect($tasksUrl);

    $foreignTodo = Todo::factory()->create();

    $this->actingAs($user)
        ->postJson(route('checklists.store', $foreignTodo), ['name' => 'Private'])
        ->assertForbidden();
});

test('user can update checklist', function () {
    [$user, $workspace, $todo, $checklist] = createAuthenticatedChecklist();

    $response = $this->actingAs($user)->putJson(route('checklists.update', $checklist->id), [
        'name' => 'Updated Checklist',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('checklists', ['id' => $checklist->id, 'name' => 'Updated Checklist']);
});

test('user can delete checklist', function () {
    [$user, $workspace, $todo, $checklist] = createAuthenticatedChecklist();

    $response = $this->actingAs($user)->deleteJson(route('checklists.destroy', $checklist->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('checklists', ['id' => $checklist->id]);
});

test('user can add checklist item', function () {
    [$user, $workspace, $todo, $checklist] = createAuthenticatedChecklist();

    $response = $this->actingAs($user)->postJson(route('checklistItems.store', $checklist->id), [
        'content' => 'Buy groceries',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('checklist_items', ['checklist_id' => $checklist->id, 'content' => 'Buy groceries', 'is_checked' => false]);
});

test('user can toggle checklist item', function () {
    [$user, $workspace, $todo, $checklist] = createAuthenticatedChecklist();
    $item = ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'is_checked' => false]);

    $response = $this->actingAs($user)->patchJson(route('checklistItems.toggle', $item->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('checklist_items', ['id' => $item->id, 'is_checked' => true]);
});

test('user can delete checklist item', function () {
    [$user, $workspace, $todo, $checklist] = createAuthenticatedChecklist();
    $item = ChecklistItem::factory()->create(['checklist_id' => $checklist->id]);

    $response = $this->actingAs($user)->deleteJson(route('checklistItems.destroy', $item->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
});
