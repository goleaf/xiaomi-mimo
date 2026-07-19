<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Inertia\Testing\AssertableInertia as Assert;

test('members settings page provides a renderable roster for the selected workspace', function () {
    $owner = User::factory()->create(['name' => 'Zelda Owner']);
    $admin = User::factory()->create(['name' => 'Alice Admin']);
    $member = User::factory()->create(['name' => 'Bob Member']);
    $foreignUser = User::factory()->create(['name' => 'Foreign User']);
    $workspace = Workspace::factory()->for($owner, 'owner')->create(['name' => 'Product Studio']);
    $foreignWorkspace = Workspace::factory()->for($foreignUser, 'owner')->create();

    foreach ([
        [$workspace, $owner, WorkspaceRole::Owner],
        [$workspace, $admin, WorkspaceRole::Admin],
        [$workspace, $member, WorkspaceRole::Member],
        [$foreignWorkspace, $foreignUser, WorkspaceRole::Owner],
    ] as [$memberWorkspace, $workspaceUser, $role]) {
        WorkspaceMember::create([
            'workspace_id' => $memberWorkspace->id,
            'user_id' => $workspaceUser->id,
            'role' => $role,
        ]);
    }

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('members.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Members')
            ->where('workspace.id', $workspace->id)
            ->where('workspace.name', 'Product Studio')
            ->where('can_manage_members', true)
            ->has('members', 3)
            ->where('members.0.id', $owner->id)
            ->where('members.0.name', 'Zelda Owner')
            ->where('members.0.role', 'owner')
            ->where('members.0.is_current_user', true)
            ->where('members.0.can_remove', false)
            ->where('members.1.id', $admin->id)
            ->where('members.1.role', 'admin')
            ->where('members.1.can_remove', true)
            ->where('members.2.id', $member->id)
            ->where('members.2.role', 'member')
            ->missing('members.0.user')
            ->where('copy.roster_title', 'Workspace roster'));
});

test('regular members receive a read only workspace scoped roster', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $foreignUser = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    foreach ([
        [$owner, WorkspaceRole::Owner],
        [$member, WorkspaceRole::Member],
    ] as [$workspaceUser, $role]) {
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $workspaceUser->id,
            'role' => $role,
        ]);
    }

    $this->actingAs($member)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('members.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('can_manage_members', false)
            ->has('members', 2)
            ->where('members.0.can_remove', false)
            ->where('members.1.can_remove', false)
            ->where('members', fn ($members) => $members->doesntContain('id', $foreignUser->id)));
});

test('members settings copy follows the supported user locale', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);
    UserPreference::create([
        'user_id' => $owner->id,
        'timezone' => 'Europe/Vilnius',
        'language' => 'ru',
    ]);

    $this->actingAs($owner)
        ->get(route('members.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'ru')
            ->where('copy.page_title', 'Участники')
            ->where('copy.invite_title', 'Пригласить участника'));
});

test('workspace owner can invite an existing user from the members page', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($owner)
        ->post(route('workspaces.invite', $workspace), [
            'email' => $invitedUser->email,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertRedirect(route('members.edit'));

    $membership = WorkspaceMember::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $invitedUser->id)
        ->first();

    $this->assertNotNull($membership);
    $this->assertModelExists($membership);
});

test('workspace owner can remove another member from the members page', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);
    $membership = WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);

    $this->actingAs($owner)
        ->delete(route('workspaces.removeMember', [$workspace, $member->id]))
        ->assertRedirect(route('members.edit'));

    $this->assertModelMissing($membership);
});

test('workspace owner cannot be removed from the members page', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();
    $membership = WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($owner)
        ->delete(route('workspaces.removeMember', [$workspace, $owner->id]))
        ->assertUnprocessable();

    $this->assertModelExists($membership);
});
