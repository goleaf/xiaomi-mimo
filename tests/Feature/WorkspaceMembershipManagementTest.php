<?php

use App\Actions\RemoveFromWorkspace;
use App\Actions\TransferWorkspaceOwnership;
use App\Actions\UpdateWorkspaceMemberRole;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

/**
 * @return array{owner: User, workspace: Workspace}
 */
function createMembershipManagementWorkspace(): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    return compact('owner', 'workspace');
}

test('canonical workspace management sections render the shared page for members', function (
    string $routeName,
    string $section,
) {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route($routeName, $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Show', false)
            ->where('section', $section)
            ->where('workspace.id', $workspace->id)
            ->where('workspace.owner.id', $owner->id)
            ->where('workspace.permissions.manage_members', true)
            ->where('workspace.permissions.transfer_ownership', true)
            ->has('members', 1)
            ->where('members.0.id', $owner->id)
            ->where('members.0.role', WorkspaceRole::Owner->value)
            ->has('invitations', 0)
            ->where('locale', app()->getLocale()));
})->with([
    'overview' => ['workspaces.show', 'overview'],
    'members' => ['workspaces.members', 'members'],
    'configuration' => ['workspaces.configuration', 'configuration'],
    'danger' => ['workspaces.danger', 'danger'],
]);

test('workspace members receive read only management data while outsiders are rejected', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);

    WorkspaceInvitation::factory()->for($workspace)->for($owner, 'inviter')->create();

    $this->actingAs($member)
        ->get(route('workspaces.members', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspace.permissions.manage_members', false)
            ->where('workspace.permissions.transfer_ownership', false)
            ->has('members', 2)
            ->has('invitations', 0));

    $this->actingAs($outsider)
        ->get(route('workspaces.show', $workspace))
        ->assertForbidden();
});

test('legacy members settings redirects to the selected workspace roster', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('members.edit'))
        ->assertRedirect(route('workspaces.members', $workspace));
});

test('workspace invitations are normalized pending records and never create users', function () {
    Notification::fake();
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $email = 'Future.Member@Example.COM';

    $this->actingAs($owner)
        ->postJson(route('workspaces.invite', $workspace), [
            'email' => $email,
            'role' => WorkspaceRole::Admin->value,
        ])
        ->assertCreated()
        ->assertJsonPath('invitation.email', 'future.member@example.com')
        ->assertJsonPath('invitation.role', WorkspaceRole::Admin->value)
        ->assertJsonMissingPath('invitation.token')
        ->assertJsonMissingPath('invitation.token_hash')
        ->assertJsonMissingPath('invitation.accept_url');

    expect(User::query()->where('email', 'future.member@example.com')->exists())->toBeFalse();

    $invitation = WorkspaceInvitation::query()->firstOrFail();

    expect($invitation->token_hash)->toHaveLength(64)
        ->and($invitation->email)->toBe('future.member@example.com')
        ->and($invitation->accepted_at)->toBeNull()
        ->and($invitation->cancelled_at)->toBeNull();

    Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
});

test('only the invited email can accept once through the expiring signed flow', function () {
    Notification::fake();
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $invitedUser = User::factory()->create(['email' => 'invitee@example.com']);
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);

    $this->actingAs($owner)->postJson(route('workspaces.invite', $workspace), [
        'email' => 'INVITEE@example.com',
        'role' => WorkspaceRole::Admin->value,
    ])->assertCreated();

    $notification = null;
    Notification::assertSentOnDemand(
        WorkspaceInvitationNotification::class,
        function (WorkspaceInvitationNotification $sent) use (&$notification): bool {
            $notification = $sent;

            return true;
        },
    );

    expect($notification)->toBeInstanceOf(WorkspaceInvitationNotification::class);
    $acceptUrl = $notification->acceptUrl();

    $this->actingAs($wrongUser)
        ->get($acceptUrl)
        ->assertForbidden();

    $this->actingAs($invitedUser)
        ->get($acceptUrl)
        ->assertRedirect(route('workspaces.members', $workspace));

    $membership = WorkspaceMember::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $invitedUser->id)
        ->firstOrFail();

    expect($membership->role)->toBe(WorkspaceRole::Admin)
        ->and(WorkspaceInvitation::query()->firstOrFail()->accepted_at)->not->toBeNull();

    $this->actingAs($invitedUser)
        ->get($acceptUrl)
        ->assertForbidden();

    expect(WorkspaceMember::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $invitedUser->id)
        ->count())->toBe(1);

    $this->actingAs($owner)
        ->postJson(route('workspaces.invite', $workspace), [
            'email' => $invitedUser->email,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(WorkspaceInvitation::query()->firstOrFail()->accepted_at)->not->toBeNull();
});

test('expired and cancelled invitations cannot be accepted and resend rotates the secret', function () {
    Notification::fake();
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $invitedUser = User::factory()->create(['email' => 'pending@example.com']);

    $this->actingAs($owner)->postJson(route('workspaces.invite', $workspace), [
        'email' => $invitedUser->email,
        'role' => WorkspaceRole::Member->value,
    ])->assertCreated();

    $invitation = WorkspaceInvitation::query()->firstOrFail();
    $originalHash = $invitation->token_hash;

    $this->actingAs($owner)
        ->postJson(route('workspaces.invitations.resend', [$workspace, $invitation]))
        ->assertOk()
        ->assertJsonMissingPath('invitation.token_hash')
        ->assertJsonMissingPath('invitation.accept_url');

    expect($invitation->refresh()->token_hash)->not->toBe($originalHash);

    $notification = null;
    Notification::assertSentOnDemand(
        WorkspaceInvitationNotification::class,
        function (WorkspaceInvitationNotification $sent) use (&$notification): bool {
            $notification = $sent;

            return true;
        },
    );

    $acceptUrl = $notification->acceptUrl();

    $this->actingAs($owner)
        ->deleteJson(route('workspaces.invitations.cancel', [$workspace, $invitation]))
        ->assertNoContent();

    $this->actingAs($invitedUser)
        ->get($acceptUrl)
        ->assertForbidden();

    expect($invitation->refresh()->cancelled_at)->not->toBeNull();

    $expiredUser = User::factory()->create(['email' => 'expired@example.com']);
    $expiredToken = 'expired-invitation-secret';
    $expired = WorkspaceInvitation::factory()->for($workspace)->for($owner, 'inviter')->expired()->create([
        'email' => $expiredUser->email,
        'token_hash' => hash('sha256', $expiredToken),
    ]);

    $expiredUrl = $expired->acceptUrl($expiredToken, now()->addMinute());

    $this->actingAs($expiredUser)
        ->get($expiredUrl)
        ->assertForbidden();
});

test('workspace managers can update roles and remove only scoped non owner members', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $foreignWorkspace = Workspace::factory()->for($foreignOwner, 'owner')->create();

    foreach ([
        [$workspace, $admin, WorkspaceRole::Admin],
        [$workspace, $member, WorkspaceRole::Member],
        [$foreignWorkspace, $foreignOwner, WorkspaceRole::Owner],
    ] as [$memberWorkspace, $workspaceUser, $role]) {
        WorkspaceMember::create([
            'workspace_id' => $memberWorkspace->id,
            'user_id' => $workspaceUser->id,
            'role' => $role,
        ]);
    }

    $this->actingAs($owner)
        ->patchJson(route('workspaces.members.update', [$workspace, $member->id]), [
            'role' => WorkspaceRole::Admin->value,
        ])
        ->assertOk()
        ->assertJsonPath('member.role', WorkspaceRole::Admin->value);

    $this->actingAs($admin)
        ->deleteJson(route('workspaces.removeMember', [$workspace, $member->id]))
        ->assertNoContent();

    expect($workspace->members()->whereKey($member->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->patchJson(route('workspaces.members.update', [$workspace, $owner->id]), [
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertUnprocessable();

    $this->actingAs($owner)
        ->deleteJson(route('workspaces.removeMember', [$workspace, $foreignOwner->id]))
        ->assertNotFound();

    expect($foreignWorkspace->members()->whereKey($foreignOwner->id)->exists())->toBeTrue()
        ->and($workspace->owner_id)->toBe($owner->id);
});

test('ownership transfer atomically preserves exactly one owner membership', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $newOwner = User::factory()->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $newOwner->id,
        'role' => WorkspaceRole::Member,
    ]);

    $this->actingAs($owner)
        ->postJson(route('workspaces.transferOwnership', $workspace), [
            'user_id' => $newOwner->id,
        ])
        ->assertOk()
        ->assertJsonPath('workspace.owner_id', $newOwner->id);

    expect($workspace->refresh()->owner_id)->toBe($newOwner->id)
        ->and($workspace->memberRole($owner))->toBe(WorkspaceRole::Admin->value)
        ->and($workspace->memberRole($newOwner))->toBe(WorkspaceRole::Owner->value)
        ->and(WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('role', WorkspaceRole::Owner->value)
            ->count())->toBe(1);

    $this->actingAs($owner)
        ->postJson(route('workspaces.transferOwnership', $workspace), [
            'user_id' => $owner->id,
        ])
        ->assertForbidden();
});

test('ownership transfer rejects foreign members without partial mutation', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $foreignOwner = User::factory()->create();
    $foreignWorkspace = Workspace::factory()->for($foreignOwner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $foreignWorkspace->id,
        'user_id' => $foreignOwner->id,
        'role' => WorkspaceRole::Owner,
    ]);

    $this->actingAs($owner)
        ->postJson(route('workspaces.transferOwnership', $workspace), [
            'user_id' => $foreignOwner->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');

    expect($workspace->refresh()->owner_id)->toBe($owner->id)
        ->and($workspace->memberRole($owner))->toBe(WorkspaceRole::Owner->value)
        ->and($foreignWorkspace->memberRole($foreignOwner))->toBe(WorkspaceRole::Owner->value);
});

test('ownership actions revalidate stale authority and protect the current owner', function () {
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $newOwner = User::factory()->create();
    $thirdMember = User::factory()->create();

    foreach ([$newOwner, $thirdMember] as $member) {
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => WorkspaceRole::Member,
        ]);
    }

    $staleNewOwnerMembership = WorkspaceMember::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $newOwner->id)
        ->firstOrFail();

    app(TransferWorkspaceOwnership::class)->handle($workspace, $owner, $newOwner);

    expect(fn () => app(TransferWorkspaceOwnership::class)
        ->handle($workspace, $owner, $thirdMember))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateWorkspaceMemberRole::class)
            ->handle($staleNewOwnerMembership, $owner, WorkspaceRole::Member))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(RemoveFromWorkspace::class)
            ->handle($staleNewOwnerMembership, $owner))
        ->toThrow(ValidationException::class);

    expect($workspace->refresh()->owner_id)->toBe($newOwner->id)
        ->and($workspace->memberRole($newOwner))->toBe(WorkspaceRole::Owner->value)
        ->and(WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('role', WorkspaceRole::Owner->value)
            ->count())->toBe(1);
});

test('workspace membership api enforces write ability and hides invitation secrets', function () {
    Notification::fake();
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $readToken = $owner->createToken('workspace-read', ['workspaces:read'])->plainTextToken;

    $this->withToken($readToken)
        ->getJson("/api/workspaces/{$workspace->id}/members")
        ->assertOk()
        ->assertJsonPath('data.0.permissions.update', false)
        ->assertJsonPath('data.0.permissions.remove', false);

    $this->withToken($readToken)
        ->postJson("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'api-invite@example.com',
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $writeToken = $owner->createToken('workspace-write', ['workspaces:read', 'workspaces:write'])->plainTextToken;

    $this->withToken($writeToken)
        ->postJson("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'API-Invite@example.com',
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertCreated()
        ->assertJsonPath('invitation.email', 'api-invite@example.com')
        ->assertJsonMissingPath('invitation.token')
        ->assertJsonMissingPath('invitation.token_hash')
        ->assertJsonMissingPath('invitation.accept_url');
});

test('workspace membership api requires read ability and always responds with json', function () {
    Notification::fake();
    ['owner' => $owner, 'workspace' => $workspace] = createMembershipManagementWorkspace();
    $unrelatedToken = $owner->createToken('unrelated', ['tasks:read'])->plainTextToken;

    $this->withToken($unrelatedToken)
        ->getJson("/api/workspaces/{$workspace->id}/members")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();

    $this->withToken($unrelatedToken)
        ->getJson("/api/workspaces/{$workspace->id}/invitations")
        ->assertForbidden();

    $this->app->make('auth')->forgetGuards();
    $writeToken = $owner->createToken('workspace-write', ['workspaces:write'])->plainTextToken;

    $this->withToken($writeToken)
        ->post("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'plain-api@example.com',
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertCreated()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonPath('invitation.email', 'plain-api@example.com');
});
