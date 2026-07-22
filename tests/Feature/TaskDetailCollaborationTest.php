<?php

use App\Enums\ApiTokenAbility;
use App\Enums\WorkspaceRole;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** @return array{user: User, workspace: Workspace, todo: Todo, token: string} */
function taskDetailActor(WorkspaceRole $role = WorkspaceRole::Owner): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_id' => $role === WorkspaceRole::Owner ? $user->id : User::factory()->create()->id,
    ]);
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => $role]);
    $todo = Todo::factory()->for($workspace)->create();
    $token = $user->createToken('task-detail', ApiTokenAbility::values())->plainTextToken;

    return compact('user', 'workspace', 'todo', 'token');
}

test('versioned checklist API supports full lifecycle and exact set ordering', function () {
    ['token' => $token, 'todo' => $todo] = taskDetailActor();
    $first = Checklist::factory()->for($todo)->create(['name' => 'First', 'position' => 1]);
    $second = Checklist::factory()->for($todo)->create(['name' => 'Second', 'position' => 2]);

    $this->withToken($token)
        ->putJson(route('api.v1.checklists.update', [$todo, $first], false), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $item = ChecklistItem::factory()->for($first)->create(['content' => 'Old', 'position' => 1]);
    $secondItem = ChecklistItem::factory()->for($first)->create(['position' => 2]);

    $this->withToken($token)
        ->putJson(route('api.v1.checklist-items.update', [$todo, $first, $item], false), ['content' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.content', 'New');

    $this->withToken($token)
        ->putJson(route('api.v1.checklist-items.reorder', [$todo, $first], false), [
            'ids' => [$secondItem->id, $item->id],
        ])
        ->assertNoContent();

    expect($secondItem->refresh()->position)->toBe(1)
        ->and($item->refresh()->position)->toBe(2);

    $this->withToken($token)
        ->putJson(route('api.v1.checklists.reorder', $todo, false), ['ids' => [$first->id]])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed');

    expect($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2);

    $this->withToken($token)
        ->deleteJson(route('api.v1.checklist-items.destroy', [$todo, $first, $item], false))
        ->assertNoContent();
    $this->withToken($token)
        ->deleteJson(route('api.v1.checklists.destroy', [$todo, $second], false))
        ->assertNoContent();

    $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('checklists', ['id' => $second->id]);
});

test('checklist item mutations are hidden when any parent does not match', function () {
    ['token' => $token, 'workspace' => $workspace, 'todo' => $todo] = taskDetailActor();
    $otherTodo = Todo::factory()->for($workspace)->create();
    $foreignChecklist = Checklist::factory()->for($otherTodo)->create();
    $foreignItem = ChecklistItem::factory()->for($foreignChecklist)->create();

    $this->withToken($token)
        ->deleteJson(route('api.v1.checklist-items.destroy', [
            $todo,
            $foreignChecklist,
            $foreignItem,
        ], false))
        ->assertNotFound();

    expect($foreignItem->fresh())->not->toBeNull();
});

test('comments are cursor paginated and expose author and moderator permissions', function () {
    ['user' => $owner, 'token' => $ownerToken, 'workspace' => $workspace, 'todo' => $todo] = taskDetailActor();
    $member = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($member)->create(['role' => WorkspaceRole::Member]);
    $memberToken = $member->createToken('member', ApiTokenAbility::values())->plainTextToken;
    Comment::factory()->count(25)->for($todo)->for($member)->create();

    $this->withToken($ownerToken)
        ->getJson(route('api.v1.comments.index', $todo, false).'?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.permissions.update', true)
        ->assertJsonPath('data.0.permissions.delete', true)
        ->assertJsonStructure(['links' => ['next'], 'meta' => ['next_cursor', 'request_id']]);

    $comment = $todo->comments()->firstOrFail();
    $this->withToken($memberToken)
        ->putJson(route('api.v1.comments.update', [$todo, $comment], false), ['body' => 'Author edit'])
        ->assertOk();

    $otherMember = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($otherMember)->create(['role' => WorkspaceRole::Member]);
    $otherMemberToken = $otherMember->createToken('other-member', ApiTokenAbility::values())->plainTextToken;

    expect($otherMember->can('delete', $comment))->toBeFalse();
    Auth::forgetGuards();

    $this->withToken($otherMemberToken)
        ->deleteJson(route('api.v1.comments.destroy', [$todo, $comment], false))
        ->assertForbidden();

    expect($comment->fresh())->not->toBeNull()
        ->and($owner->can('delete', $comment))->toBeTrue();
});

test('task detail bounds comments and never exposes private attachment paths', function () {
    Storage::fake('local');
    config()->set('filesystems.attachment_disk', 'local');
    ['user' => $user, 'token' => $token, 'todo' => $todo] = taskDetailActor();
    Comment::factory()->count(25)->for($todo)->for($user)->create();

    Storage::disk('local')->put('attachments/private.txt', 'private');
    $attachment = Attachment::factory()->for($todo)->for($user)->create([
        'filename' => 'notes.txt',
        'path' => 'attachments/private.txt',
        'mime_type' => 'text/plain',
        'size' => 7,
    ]);

    $detail = $this->withToken($token)
        ->getJson(route('api.v1.tasks.show', [$todo->workspace_id, $todo], false))
        ->assertOk()
        ->assertJsonCount(20, 'data.comments')
        ->assertJsonPath('data.comments_count', 25)
        ->assertJsonMissingPath('data.attachments.0.path')
        ->assertJsonMissingPath('data.attachments.0.url')
        ->assertJsonPath(
            'data.attachments.0.download_url',
            route('api.v1.attachments.download', [$todo, $attachment], false),
        );

    $downloadUrl = (string) $detail->json('data.attachments.0.download_url');
    $this->withToken($token)
        ->get($downloadUrl)
        ->assertOk()
        ->assertDownload('notes.txt')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('task taxonomy recurrence reminders and attachments use scoped detail contracts', function () {
    Storage::fake('local');
    config()->set('filesystems.attachment_disk', 'local');
    ['token' => $token, 'workspace' => $workspace, 'todo' => $todo] = taskDetailActor();
    $label = Label::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();

    $this->withToken($token)
        ->postJson(route('api.v1.labels.attach', [$workspace, $todo], false), ['label_id' => $label->id])
        ->assertOk()
        ->assertJsonPath('data.id', $label->id);
    $this->withToken($token)
        ->postJson(route('api.v1.tags.attach', [$workspace, $todo], false), ['tag_id' => $tag->id])
        ->assertOk()
        ->assertJsonPath('data.id', $tag->id);

    expect($todo->labels()->whereKey($label)->exists())->toBeTrue()
        ->and($todo->tags()->whereKey($tag)->exists())->toBeTrue();

    $this->withToken($token)
        ->putJson(route('api.v1.tasks.update', [$workspace, $todo], false), [
            'is_recurring' => true,
            'recurring_rule' => 'FREQ=WEEKLY;INTERVAL=2',
        ])
        ->assertOk();
    $this->withToken($token)
        ->putJson(route('api.v1.tasks.update', [$workspace, $todo], false), [
            'is_recurring' => true,
            'recurring_rule' => 'UNBOUNDED',
        ])
        ->assertUnprocessable();

    expect($todo->refresh()->is_recurring)->toBeTrue()
        ->and($todo->recurring_rule)->toBe('FREQ=WEEKLY;INTERVAL=2');

    $this->withToken($token)
        ->postJson(route('api.v1.reminders.store', $todo, false), [
            'reminded_at' => now()->addHour()->toIso8601String(),
            'type' => 'browser',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'browser');

    $upload = $this->withToken($token)
        ->post(route('api.v1.attachments.store', $todo, false), [
            'file' => UploadedFile::fake()->create('brief.txt', 8, 'text/plain'),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.filename', 'brief.txt')
        ->assertJsonMissingPath('data.path');

    Storage::disk('local')->assertExists(
        Attachment::query()->findOrFail($upload->json('data.id'))->path,
    );

    $this->withToken($token)
        ->deleteJson(route('api.v1.labels.detach', [$workspace, $todo, $label], false))
        ->assertNoContent();
    $this->withToken($token)
        ->deleteJson(route('api.v1.tags.detach', [$workspace, $todo, $tag], false))
        ->assertNoContent();
});

test('task detail entry points share the complete collaboration surface', function () {
    $drawer = File::get(resource_path('js/components/task/TaskDetail.vue'));
    $page = File::get(resource_path('js/pages/tasks/Show.vue'));
    $content = File::get(resource_path('js/components/task/TaskDetailContent.vue'));

    expect($drawer)
        ->toContain('TaskDetailContent')
        ->not->toContain("from '@/routes/api/v1/tasks'")
        ->and(substr_count($drawer, "\n"))->toBeLessThan(100)
        ->and($page)
        ->toContain('TaskDetailContent')
        ->not->toContain("from '@/routes/api/v1/tasks'")
        ->and(substr_count($page, "\n"))->toBeLessThan(100)
        ->and($content)
        ->toContain('TaskOverviewPanel')
        ->toContain('TaskTaxonomyPanel')
        ->toContain('TaskChecklistPanel')
        ->toContain('TaskCommentsPanel')
        ->toContain('TaskRemindersPanel')
        ->toContain('TaskAttachmentsPanel');

    expect(File::get(resource_path('js/pages/tasks/Index.vue')))
        ->toContain('taskDetailTrigger.value =')
        ->toContain('taskDetailTrigger.value?.focus()')
        ->toContain('@close="closeTaskDetail"');
});
