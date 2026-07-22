<?php

use App\Enums\WorkspaceRole;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ProjectIndexController;
use App\Http\Controllers\TodoIndexController;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace shortcut routes use page controllers instead of closures', function () {
    $actions = [
        'todos.index' => TodoIndexController::class,
        'projects' => ProjectIndexController::class.'@current',
        'activity' => ActivityController::class.'@current',
    ];

    foreach ($actions as $name => $expectedAction) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route?->getActionName())->toBe($expectedAction);
    }
});

test('workspace shortcut pages keep complete empty states without a membership', function (string $routeName, string $component) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($component, $routeName): void {
            $page->component($component);

            match ($routeName) {
                'todos.index' => $page
                    ->where('workspace.id', '')
                    ->has('todos.data', 0)
                    ->has('projects.data', 0)
                    ->has('taskDefinitions.statuses', 0)
                    ->has('taskDefinitions.priorities', 0),
                'projects' => $page
                    ->where('workspace.id', '')
                    ->has('projects.data', 0),
                'activity' => $page->has('activities.data', 0),
                default => throw new LogicException("Unexpected route [{$routeName}]."),
            };
        });
})->with([
    'tasks' => ['todos.index', 'tasks/Index'],
    'projects' => ['projects', 'projects/Index'],
    'activity' => ['activity', 'activity/Index'],
]);

test('task shortcut always renders the Inertia page while external JSON uses the API', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);
    Todo::factory()->for($workspace)->create(['title' => 'Page task']);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->withHeader('Accept', 'application/json')
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Index')
            ->where('workspace.id', $workspace->id)
            ->where('todos.data.0.title', 'Page task'));
});

test('web controllers do not select response formats from request headers', function () {
    foreach ([
        'TodoController.php',
        'ChecklistController.php',
        'CommentController.php',
        'WorkspaceInvitationController.php',
        'WorkspaceMemberController.php',
        'WorkspaceOwnershipController.php',
    ] as $controller) {
        expect(File::get(app_path("Http/Controllers/{$controller}")))
            ->not->toContain('expectsJson()')
            ->not->toContain("is('api/*')");
    }
});

test('first party session requests can use the canonical task API', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);
    $todo = Todo::factory()->for($workspace)->create(['title' => 'Before']);

    $this->actingAs($user)
        ->putJson(route('api.v1.tasks.update', [$workspace, $todo], false), ['title' => 'After'])
        ->assertOk()
        ->assertJsonPath('data.title', 'After');

    expect($todo->refresh()->title)->toBe('After');
});

test('web project bindings reject a project paired with another workspace', function () {
    $user = User::factory()->create();
    $firstWorkspace = Workspace::factory()->for($user, 'owner')->create();
    $secondWorkspace = Workspace::factory()->for($user, 'owner')->create();

    foreach ([$firstWorkspace, $secondWorkspace] as $workspace) {
        WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);
    }

    $project = Project::factory()->for($secondWorkspace)->create(['name' => 'Private pairing']);

    $this->actingAs($user)
        ->get(route('projects.show', [$firstWorkspace, $project]))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('projects.update', [$firstWorkspace, $project]), ['name' => 'Leaked'])
        ->assertNotFound();

    expect($project->refresh()->name)->toBe('Private pairing');
});
