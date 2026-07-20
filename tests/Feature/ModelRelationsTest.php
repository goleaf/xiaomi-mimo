<?php

use App\Enums\WorkspaceRole;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

test('model relationships expose their declared Eloquent relation type', function (string $model, string $relation, string $expectedType) {
    expect((new $model)->{$relation}())->toBeInstanceOf($expectedType);
})->with([
    'activity user' => [ActivityLog::class, 'user', BelongsTo::class],
    'activity workspace' => [ActivityLog::class, 'workspace', BelongsTo::class],
    'activity subject' => [ActivityLog::class, 'subject', MorphTo::class],
    'attachment todo' => [Attachment::class, 'todo', BelongsTo::class],
    'attachment user' => [Attachment::class, 'user', BelongsTo::class],
    'checklist todo' => [Checklist::class, 'todo', BelongsTo::class],
    'checklist items' => [Checklist::class, 'items', HasMany::class],
    'checklist item checklist' => [ChecklistItem::class, 'checklist', BelongsTo::class],
    'comment todo' => [Comment::class, 'todo', BelongsTo::class],
    'comment user' => [Comment::class, 'user', BelongsTo::class],
    'label workspace' => [Label::class, 'workspace', BelongsTo::class],
    'label todos' => [Label::class, 'todos', BelongsToMany::class],
    'project workspace' => [Project::class, 'workspace', BelongsTo::class],
    'project todos' => [Project::class, 'todos', HasMany::class],
    'reminder todo' => [Reminder::class, 'todo', BelongsTo::class],
    'reminder user' => [Reminder::class, 'user', BelongsTo::class],
    'tag workspace' => [Tag::class, 'workspace', BelongsTo::class],
    'tag todos' => [Tag::class, 'todos', BelongsToMany::class],
    'todo project' => [Todo::class, 'project', BelongsTo::class],
    'todo workspace' => [Todo::class, 'workspace', BelongsTo::class],
    'todo assignee' => [Todo::class, 'assignee', BelongsTo::class],
    'todo parent' => [Todo::class, 'parent', BelongsTo::class],
    'todo subtasks' => [Todo::class, 'subtasks', HasMany::class],
    'todo comments' => [Todo::class, 'comments', HasMany::class],
    'todo checklists' => [Todo::class, 'checklists', HasMany::class],
    'todo reminders' => [Todo::class, 'reminders', HasMany::class],
    'todo attachments' => [Todo::class, 'attachments', HasMany::class],
    'todo labels' => [Todo::class, 'labels', BelongsToMany::class],
    'todo tags' => [Todo::class, 'tags', BelongsToMany::class],
    'todo activity logs' => [Todo::class, 'activityLogs', MorphMany::class],
    'user owned workspaces' => [User::class, 'ownedWorkspaces', HasMany::class],
    'user workspaces' => [User::class, 'workspaces', BelongsToMany::class],
    'user assigned todos' => [User::class, 'assignedTodos', HasMany::class],
    'user comments' => [User::class, 'comments', HasMany::class],
    'user activity logs' => [User::class, 'activityLogs', HasMany::class],
    'user preferences' => [User::class, 'preferences', HasOne::class],
    'user preference user' => [UserPreference::class, 'user', BelongsTo::class],
    'workspace owner' => [Workspace::class, 'owner', BelongsTo::class],
    'workspace members' => [Workspace::class, 'members', BelongsToMany::class],
    'workspace projects' => [Workspace::class, 'projects', HasMany::class],
    'workspace todos' => [Workspace::class, 'todos', HasMany::class],
    'workspace labels' => [Workspace::class, 'labels', HasMany::class],
    'workspace tags' => [Workspace::class, 'tags', HasMany::class],
    'workspace activity logs' => [Workspace::class, 'activityLogs', HasMany::class],
    'workspace member workspace' => [WorkspaceMember::class, 'workspace', BelongsTo::class],
    'workspace member user' => [WorkspaceMember::class, 'user', BelongsTo::class],
]);

test('workspace member roles are read from the membership pivot', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Admin,
    ]);

    expect($workspace->memberRole($member))->toBe(WorkspaceRole::Admin->value);
});

test('workspace membership checks reuse an already loaded pivot without queries', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Admin,
    ]);

    $workspaceFromMembership = $member->workspaces()->whereKey($workspace->id)->firstOrFail();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect($workspaceFromMembership->hasMember($member))->toBeTrue()
        ->and($workspaceFromMembership->memberRole($member))->toBe(WorkspaceRole::Admin->value)
        ->and(DB::getQueryLog())->toBeEmpty();
});
