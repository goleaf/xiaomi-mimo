<?php

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * @return array{user: User, workspace: Workspace}
 */
function createWorkspaceMetadataActor(WorkspaceRole $role = WorkspaceRole::Owner): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();
    $user = $role === WorkspaceRole::Owner ? $owner : User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    if ($role !== WorkspaceRole::Owner) {
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    return compact('user', 'workspace');
}

test('workspace members can list labels and tags with usage counts but not manage them', function () {
    ['user' => $member, 'workspace' => $workspace] = createWorkspaceMetadataActor(WorkspaceRole::Member);
    $todo = Todo::factory()->for($workspace)->create();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    $todo->labels()->attach($label);
    $todo->tags()->attach($tag);

    $this->actingAs($member)
        ->getJson(route('labels.index', $workspace))
        ->assertOk()
        ->assertJsonPath('labels.0.id', $label->id)
        ->assertJsonPath('labels.0.todos_count', 1)
        ->assertJsonPath('labels.0.permissions.update', false)
        ->assertJsonPath('labels.0.permissions.delete', false);

    $this->actingAs($member)
        ->getJson(route('tags.index', $workspace))
        ->assertOk()
        ->assertJsonPath('tags.0.id', $tag->id)
        ->assertJsonPath('tags.0.todos_count', 1)
        ->assertJsonPath('tags.0.permissions.update', false)
        ->assertJsonPath('tags.0.permissions.delete', false);
});

test('task configuration page exposes scoped labels tags counts and permissions', function () {
    ['user' => $owner, 'workspace' => $workspace] = createWorkspaceMetadataActor();
    ['workspace' => $foreignWorkspace] = createWorkspaceMetadataActor();
    $todo = Todo::factory()->for($workspace)->create();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    Label::factory()->for($foreignWorkspace)->create();
    Tag::factory()->for($foreignWorkspace)->create();
    $todo->labels()->attach($label);
    $todo->tags()->attach($tag);

    $this->actingAs($owner)
        ->get(route('workspaces.configuration', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Show', false)
            ->where('section', 'configuration')
            ->where('workspace.permissions.manage_task_configuration', true)
            ->has('labels', 1)
            ->where('labels.0.id', $label->id)
            ->where('labels.0.todos_count', 1)
            ->where('labels.0.permissions.update', true)
            ->has('tags', 1)
            ->where('tags.0.id', $tag->id)
            ->where('tags.0.todos_count', 1));
});

test('owners and admins have complete normalized label and tag crud', function (WorkspaceRole $role) {
    ['user' => $manager, 'workspace' => $workspace] = createWorkspaceMetadataActor($role);

    $labelResponse = $this->actingAs($manager)
        ->postJson(route('labels.store', $workspace), [
            'name' => '  Customer Care  ',
            'color' => '#ef4444',
        ])
        ->assertCreated()
        ->assertJsonPath('label.name', 'Customer Care')
        ->assertJsonPath('label.todos_count', 0)
        ->assertJsonPath('label.permissions.update', true);

    $label = Label::query()->findOrFail($labelResponse->json('label.id'));

    $this->actingAs($manager)
        ->putJson(route('labels.update', [$workspace, $label]), [
            'name' => 'Customer Success',
            'color' => '#0ea5e9',
        ])
        ->assertOk()
        ->assertJsonPath('label.name', 'Customer Success')
        ->assertJsonPath('label.color', '#0ea5e9');

    $this->actingAs($manager)
        ->postJson(route('labels.store', $workspace), [
            'name' => 'customer success',
            'color' => '#22c55e',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $tagResponse = $this->actingAs($manager)
        ->postJson(route('tags.store', $workspace), ['name' => '  launch  '])
        ->assertCreated()
        ->assertJsonPath('tag.name', 'launch')
        ->assertJsonPath('tag.todos_count', 0)
        ->assertJsonPath('tag.permissions.delete', true);

    $tag = Tag::query()->findOrFail($tagResponse->json('tag.id'));

    $this->actingAs($manager)
        ->putJson(route('tags.update', [$workspace, $tag]), ['name' => 'release'])
        ->assertOk()
        ->assertJsonPath('tag.name', 'release');

    $this->actingAs($manager)
        ->deleteJson(route('labels.destroy', [$workspace, $label]))
        ->assertNoContent();
    $this->actingAs($manager)
        ->deleteJson(route('tags.destroy', [$workspace, $tag]))
        ->assertNoContent();

    $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
})->with([
    'owner' => WorkspaceRole::Owner,
    'admin' => WorkspaceRole::Admin,
]);

test('ordinary members cannot create update or delete task metadata', function () {
    ['user' => $member, 'workspace' => $workspace] = createWorkspaceMetadataActor(WorkspaceRole::Member);
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();

    $this->actingAs($member)
        ->postJson(route('labels.store', $workspace), ['name' => 'Blocked', 'color' => '#ef4444'])
        ->assertForbidden();
    $this->actingAs($member)
        ->putJson(route('labels.update', [$workspace, $label]), ['name' => 'Blocked'])
        ->assertForbidden();
    $this->actingAs($member)
        ->deleteJson(route('tags.destroy', [$workspace, $tag]))
        ->assertForbidden();

    expect($label->fresh())->not->toBeNull()
        ->and($tag->fresh())->not->toBeNull();
});

test('metadata mutation identifiers are scoped to the authorized workspace', function () {
    ['user' => $owner, 'workspace' => $workspace] = createWorkspaceMetadataActor();
    ['workspace' => $foreignWorkspace] = createWorkspaceMetadataActor();
    $foreignLabel = Label::factory()->for($foreignWorkspace)->create();
    $foreignTag = Tag::factory()->for($foreignWorkspace)->create();

    $this->actingAs($owner)
        ->putJson(route('labels.update', [$workspace, $foreignLabel]), ['name' => 'Leaked'])
        ->assertNotFound();
    $this->actingAs($owner)
        ->deleteJson(route('tags.destroy', [$workspace, $foreignTag]))
        ->assertNotFound();

    expect($foreignLabel->refresh()->name)->not->toBe('Leaked')
        ->and($foreignTag->fresh())->not->toBeNull();
});

test('task label and tag attachment rejects mixed workspace identifiers atomically', function () {
    ['user' => $owner, 'workspace' => $workspace] = createWorkspaceMetadataActor();
    ['workspace' => $foreignWorkspace] = createWorkspaceMetadataActor();
    $todo = Todo::factory()->for($workspace)->create();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    $foreignLabel = Label::factory()->for($foreignWorkspace)->create();
    $foreignTag = Tag::factory()->for($foreignWorkspace)->create();

    $this->actingAs($owner)
        ->postJson(route('labels.attach', [$workspace, $todo]), ['label_id' => $label->id])
        ->assertNoContent();
    $this->actingAs($owner)
        ->postJson(route('tags.attach', [$workspace, $todo]), ['tag_id' => $tag->id])
        ->assertNoContent();

    $this->actingAs($owner)
        ->postJson(route('labels.attach', [$workspace, $todo]), ['label_id' => $foreignLabel->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label_id');
    $this->actingAs($owner)
        ->postJson(route('tags.attach', [$workspace, $todo]), ['tag_id' => $foreignTag->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tag_id');

    expect($todo->fresh()->labels->modelKeys())->toBe([$label->id])
        ->and($todo->fresh()->tags->modelKeys())->toBe([$tag->id]);

    $this->actingAs($owner)
        ->deleteJson(route('labels.detach', [$workspace, $todo, $label]))
        ->assertNoContent();
    $this->actingAs($owner)
        ->deleteJson(route('tags.detach', [$workspace, $todo, $tag]))
        ->assertNoContent();

    expect($todo->fresh()->labels)->toBeEmpty()
        ->and($todo->fresh()->tags)->toBeEmpty();
});

test('deleting used metadata removes pivot relations without deleting tasks', function () {
    ['user' => $owner, 'workspace' => $workspace] = createWorkspaceMetadataActor();
    $todo = Todo::factory()->for($workspace)->create();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    $todo->labels()->attach($label);
    $todo->tags()->attach($tag);

    $this->actingAs($owner)->deleteJson(route('labels.destroy', [$workspace, $label]))->assertNoContent();
    $this->actingAs($owner)->deleteJson(route('tags.destroy', [$workspace, $tag]))->assertNoContent();

    expect($todo->fresh())->not->toBeNull();
    $this->assertDatabaseMissing('todo_label', ['todo_id' => $todo->id, 'label_id' => $label->id]);
    $this->assertDatabaseMissing('todo_tag', ['todo_id' => $todo->id, 'tag_id' => $tag->id]);
});

test('unicode case variants are rejected within the same workspace', function () {
    ['user' => $owner, 'workspace' => $workspace] = createWorkspaceMetadataActor();
    Label::factory()->for($workspace)->create(['name' => 'Žyma']);
    Tag::factory()->for($workspace)->create(['name' => 'Тег']);

    $this->actingAs($owner)
        ->postJson(route('labels.store', $workspace), ['name' => 'žYMA', 'color' => '#ef4444'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
    $this->actingAs($owner)
        ->postJson(route('tags.store', $workspace), ['name' => 'тЕГ'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('metadata uniqueness migration merges normalized duplicates and preserves task pivots', function () {
    DB::statement('DROP INDEX labels_workspace_name_unique');
    DB::statement('DROP INDEX tags_workspace_name_unique');

    ['workspace' => $workspace] = createWorkspaceMetadataActor();
    $firstTodo = Todo::factory()->for($workspace)->create();
    $secondTodo = Todo::factory()->for($workspace)->create();
    $canonicalLabel = Label::factory()->for($workspace)->create([
        'name' => 'Žyma',
        'created_at' => now()->subMinute(),
    ]);
    $duplicateLabel = Label::factory()->for($workspace)->create(['name' => ' žYMA ']);
    $canonicalTag = Tag::factory()->for($workspace)->create([
        'name' => 'Тег',
        'created_at' => now()->subMinute(),
    ]);
    $duplicateTag = Tag::factory()->for($workspace)->create(['name' => ' тЕГ ']);
    $firstTodo->labels()->attach($canonicalLabel);
    $secondTodo->labels()->attach($duplicateLabel);
    $firstTodo->tags()->attach($canonicalTag);
    $secondTodo->tags()->attach($duplicateTag);

    $migration = require database_path(
        'migrations/2026_07_20_181649_add_workspace_name_unique_indexes_to_labels_and_tags.php',
    );
    $migration->up();

    $this->assertDatabaseHas('labels', [
        'id' => $canonicalLabel->id,
        'name' => 'Žyma',
        'normalized_name' => 'žyma',
    ]);
    $this->assertDatabaseMissing('labels', ['id' => $duplicateLabel->id]);
    $this->assertDatabaseHas('tags', [
        'id' => $canonicalTag->id,
        'name' => 'Тег',
        'normalized_name' => 'тег',
    ]);
    $this->assertDatabaseMissing('tags', ['id' => $duplicateTag->id]);
    expect($canonicalLabel->fresh()->todos()->count())->toBe(2)
        ->and($canonicalTag->fresh()->todos()->count())->toBe(2);
});
