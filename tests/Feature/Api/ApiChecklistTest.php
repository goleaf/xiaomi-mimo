<?php

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createApiChecklistUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'owner']);
    $todo = Todo::factory()->create(['workspace_id' => $workspace->id]);

    return [$user, $token, $workspace, $todo];
}

test('API user can list checklists for todo', function () {
    [$user, $token, $workspace, $todo] = createApiChecklistUser();
    Checklist::factory()->count(2)->create(['todo_id' => $todo->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/tasks/{$todo->id}/checklists");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('API user can create checklist', function () {
    [$user, $token, $workspace, $todo] = createApiChecklistUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/tasks/{$todo->id}/checklists", ['name' => 'Shopping List']);

    $response->assertCreated();
    $this->assertDatabaseHas('checklists', ['todo_id' => $todo->id, 'name' => 'Shopping List']);
});

test('API user can add checklist item', function () {
    [$user, $token, $workspace, $todo] = createApiChecklistUser();
    $checklist = Checklist::factory()->create(['todo_id' => $todo->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/checklists/{$checklist->id}/items", ['content' => 'Milk']);

    $response->assertCreated();
    $this->assertDatabaseHas('checklist_items', ['checklist_id' => $checklist->id, 'content' => 'Milk']);
});

test('API user can toggle checklist item', function () {
    [$user, $token, $workspace, $todo] = createApiChecklistUser();
    $checklist = Checklist::factory()->create(['todo_id' => $todo->id]);
    $item = ChecklistItem::factory()->create([
        'checklist_id' => $checklist->id,
        'is_checked' => false,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/checklist-items/{$item->id}/toggle");

    $response->assertOk();
    $this->assertDatabaseHas('checklist_items', ['id' => $item->id, 'is_checked' => true]);
});

test('checklist API writes reject resources from another workspace', function () {
    [$user, $token] = createApiChecklistUser();
    [$foreignUser, $foreignToken, $foreignWorkspace, $foreignTodo] = createApiChecklistUser();
    $foreignChecklist = Checklist::factory()->create(['todo_id' => $foreignTodo->id]);
    $foreignItem = ChecklistItem::factory()->create([
        'checklist_id' => $foreignChecklist->id,
        'is_checked' => false,
    ]);

    $this->withToken($token)
        ->postJson("/api/tasks/{$foreignTodo->id}/checklists", ['name' => 'Leaked'])
        ->assertForbidden();
    $this->withToken($token)
        ->postJson("/api/checklists/{$foreignChecklist->id}/items", ['content' => 'Leaked'])
        ->assertForbidden();
    $this->withToken($token)
        ->patchJson("/api/checklist-items/{$foreignItem->id}/toggle")
        ->assertForbidden();

    expect($foreignItem->refresh()->is_checked)->toBeFalse();
});
