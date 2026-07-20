<?php

use App\Enums\WorkspaceRole;
use App\Models\ActivityLog;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('activity'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the activity page', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('activity'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('activity/Index'));
});

test('activity page displays activity logs', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $todo = Todo::factory()->create([
        'title' => 'Test Todo',
        'workspace_id' => $workspace->id,
        'assigned_to' => $user->id,
        'position' => 0,
    ]);

    ActivityLog::create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'subject_type' => Todo::class,
        'subject_id' => $todo->id,
        'event' => 'created',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('activity'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('activity/Index')
        ->has('activities.data', 1)
    );
});

test('activity page shows empty state when no logs exist', function () {
    $user = User::factory()->create();
    $workspace = Workspace::create([
        'name' => 'Test Workspace',
        'slug' => 'test-workspace',
        'owner_id' => $user->id,
    ]);
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('activity'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('activity/Index')
        ->has('activities.data', 0)
    );
});

test('activity page shows empty state when user has no workspace', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('activity'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('activity/Index')
        ->where('activities.data', [])
    );
});
