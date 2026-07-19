<?php

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
use Database\Factories\ActivityLogFactory;
use Database\Factories\AttachmentFactory;
use Database\Factories\ChecklistFactory;
use Database\Factories\ChecklistItemFactory;
use Database\Factories\CommentFactory;
use Database\Factories\LabelFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\ReminderFactory;
use Database\Factories\TagFactory;
use Database\Factories\TodoFactory;
use Database\Factories\UserFactory;
use Database\Factories\UserPreferenceFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMemberFactory;
use Illuminate\Support\Facades\Schema;

test('factories resolve their concrete eloquent model', function (string $factory, string $model) {
    expect($factory::new()->modelName())->toBe($model);
})->with([
    'activity log' => [ActivityLogFactory::class, ActivityLog::class],
    'attachment' => [AttachmentFactory::class, Attachment::class],
    'checklist' => [ChecklistFactory::class, Checklist::class],
    'checklist item' => [ChecklistItemFactory::class, ChecklistItem::class],
    'comment' => [CommentFactory::class, Comment::class],
    'label' => [LabelFactory::class, Label::class],
    'project' => [ProjectFactory::class, Project::class],
    'reminder' => [ReminderFactory::class, Reminder::class],
    'tag' => [TagFactory::class, Tag::class],
    'todo' => [TodoFactory::class, Todo::class],
    'user' => [UserFactory::class, User::class],
    'user preference' => [UserPreferenceFactory::class, UserPreference::class],
    'workspace' => [WorkspaceFactory::class, Workspace::class],
    'workspace member' => [WorkspaceMemberFactory::class, WorkspaceMember::class],
]);

test('supporting factories produce valid typed state', function () {
    $subject = Todo::factory()->create();
    $activity = ActivityLog::factory()
        ->forSubject($subject)
        ->withProperties(['field' => 'title'])
        ->create();
    $preference = UserPreference::factory()->create();

    expect($activity->subject_id)
        ->toBe($subject->id)
        ->and($activity->properties)->toBe(['field' => 'title'])
        ->and(timezone_open($preference->timezone))->not->toBeFalse();
});

test('sanctum stateful domains are normalized to strings', function () {
    expect(config('sanctum.stateful'))
        ->toBeArray()
        ->each->toBeString();
});

test('workspace schema enforces its declared foreign keys', function (string $table, int $expectedCount) {
    expect(Schema::getForeignKeys($table))->toHaveCount($expectedCount);
})->with([
    'workspaces' => ['workspaces', 1],
    'workspace members' => ['workspace_members', 2],
    'projects' => ['projects', 1],
    'todos' => ['todos', 3],
    'checklists' => ['checklists', 1],
    'checklist items' => ['checklist_items', 1],
    'labels' => ['labels', 1],
    'tags' => ['tags', 1],
    'todo labels' => ['todo_label', 2],
    'todo tags' => ['todo_tag', 2],
    'comments' => ['comments', 2],
    'reminders' => ['reminders', 2],
    'attachments' => ['attachments', 2],
    'activity logs' => ['activity_logs', 2],
    'user preferences' => ['user_preferences', 1],
]);

test('database seeders create the complete demo dataset', function () {
    $this->seed();

    expect(User::where('email', 'demo@example.com')->exists())->toBeTrue()
        ->and(Todo::query()->exists())->toBeTrue()
        ->and(Workspace::where('slug', 'acme-projects')->exists())->toBeTrue();
});
