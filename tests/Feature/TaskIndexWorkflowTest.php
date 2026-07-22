<?php

use App\Actions\BulkUpdateTodos;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function taskIndexWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);

    return [$user, $workspace];
}

test('task index validates and returns the complete URL backed state', function () {
    [$user, $workspace] = taskIndexWorkspace();
    $project = Project::factory()->for($workspace)->create();
    Todo::factory()->count(30)->for($workspace)->for($project)->pending()->create([
        'title' => 'Release task',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('todos.index', [
            'search' => 'Release',
            'project_id' => $project->id,
            'sort' => 'title',
            'direction' => 'desc',
            'per_page' => 25,
            'view' => 'board',
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tasks/Index')
        ->where('filters.search', 'Release')
        ->where('filters.project_id', $project->id)
        ->where('filters.sort', 'title')
        ->where('filters.direction', 'desc')
        ->where('filters.per_page', 25)
        ->where('filters.view', 'board')
        ->where('stats.total', 30)
        ->has('todos.data', 25));

    expect($response->getContent())->toContain(
        'search=Release',
        'project_id='.$project->id,
        'sort=title',
        'direction=desc',
        'per_page=25',
        'view=board',
    );

    $this->actingAs($user)
        ->from(route('todos.index'))
        ->get(route('todos.index', ['sort' => 'invalid', 'per_page' => 500, 'view' => 'grid']))
        ->assertRedirect(route('todos.index'))
        ->assertSessionHasErrors(['sort', 'per_page', 'view']);
});

test('task index metrics represent all filtered tasks instead of only the current page', function () {
    [$user, $workspace] = taskIndexWorkspace();
    Todo::factory()->count(30)->for($workspace)->pending()->create();
    Todo::factory()->count(20)->for($workspace)->completed()->create();

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('todos.index', ['per_page' => 25]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.total', 50)
            ->where('stats.pending', 30)
            ->where('stats.completed', 20)
            ->has('todos.data', 25));
});

test('task index uses the saved board preference unless the URL overrides it', function () {
    [$user, $workspace] = taskIndexWorkspace();
    $user->preferences()->create(['default_view' => 'board']);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.view', 'board'));

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('todos.index', ['view' => 'list']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.view', 'list'));
});

test('bulk actions reject stale or duplicate sets before mutating any task', function () {
    [$user, $workspace] = taskIndexWorkspace();
    $valid = Todo::factory()->for($workspace)->create(['is_archived' => false]);
    $deleted = Todo::factory()->for($workspace)->create(['is_archived' => false]);
    $deleted->delete();

    $this->actingAs($user)
        ->post(route('todos.bulk', $workspace), [
            'ids' => [$valid->id, $deleted->id],
            'action' => 'archive',
        ])
        ->assertSessionHasErrors('ids.1');

    expect($valid->refresh()->is_archived)->toBeFalse();

    expect(fn () => app(BulkUpdateTodos::class)->setArchived(
        $workspace,
        [$valid->id, $deleted->id],
        true,
    ))->toThrow(ValidationException::class);

    expect($valid->refresh()->is_archived)->toBeFalse();

    $this->actingAs($user)
        ->post(route('todos.bulk', $workspace), [
            'ids' => [$valid->id, $valid->id],
            'action' => 'delete',
        ])
        ->assertSessionHasErrors('ids.1');

    expect($valid->fresh())->not->toBeNull();
});

test('task index coordinates the reusable workflow components', function () {
    $page = File::get(resource_path('js/pages/tasks/Index.vue'));

    expect(substr_count($page, "\n"))->toBeLessThan(400)
        ->and($page)->toContain(
            '@/components/task/BoardView.vue',
            '@/components/task/BulkActions.vue',
            '@/components/task/TaskFilterBar.vue',
            '@/components/task/TaskList.vue',
            '@/components/task/TaskPagination.vue',
            'only: [\'todos\', \'filters\', \'stats\']',
        )
        ->and(File::get(resource_path('js/components/task/BoardView.vue')))->toContain(
            'taskDefinitions.statuses',
            '@keydown',
            'overflow-x-auto',
        )
        ->and(File::get(resource_path('js/components/task/BulkActions.vue')))->toContain(
            ':disabled="processing"',
            'aria-live="polite"',
        );
});
