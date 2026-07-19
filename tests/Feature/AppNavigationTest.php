<?php

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Inertia\Testing\AssertableInertia as Assert;

test('task pages receive shared sidebar navigation for the selected workspace', function () {
    $user = User::factory()->create();
    $firstWorkspace = Workspace::factory()->for($user, 'owner')->create(['name' => 'First workspace']);
    $selectedWorkspace = Workspace::factory()->for($user, 'owner')->create(['name' => 'Selected workspace']);

    WorkspaceMember::create([
        'workspace_id' => $firstWorkspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    WorkspaceMember::create([
        'workspace_id' => $selectedWorkspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);

    Project::factory()->for($selectedWorkspace)->create(['name' => 'Sidebar project']);
    Workspace::factory()->create(['name' => 'Foreign workspace']);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $selectedWorkspace->id])
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Index')
            ->where('workspace.id', $selectedWorkspace->id)
            ->where('currentWorkspace.id', $selectedWorkspace->id)
            ->where('navigation.currentWorkspace.id', $selectedWorkspace->id)
            ->where('navigation.currentWorkspace.name', 'Selected workspace')
            ->has('navigation.workspaces', 2)
            ->has('navigation.projects', 1)
            ->where('navigation.projects.0.name', 'Sidebar project')
            ->where('navigation.labels.tasks', 'Tasks')
            ->where('navigation.labels.projects', 'Projects'));
});

test('switching workspace updates subsequent shortcut pages', function () {
    $user = User::factory()->create();
    $firstWorkspace = Workspace::factory()->for($user, 'owner')->create();
    $selectedWorkspace = Workspace::factory()->for($user, 'owner')->create();

    foreach ([$firstWorkspace, $selectedWorkspace] as $workspace) {
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner,
        ]);
    }

    $this->actingAs($user)
        ->postJson(route('workspaces.switch', $selectedWorkspace))
        ->assertOk();

    $this->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspace.id', $selectedWorkspace->id)
            ->where('navigation.currentWorkspace.id', $selectedWorkspace->id));
});

test('sidebar navigation uses the supported user locale', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    UserPreference::create([
        'user_id' => $user->id,
        'timezone' => 'Europe/Vilnius',
        'language' => 'ru',
    ]);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigation.labels.dashboard', 'Обзор')
            ->where('navigation.labels.tasks', 'Задачи')
            ->where('navigation.labels.settings', 'Настройки'));
});
