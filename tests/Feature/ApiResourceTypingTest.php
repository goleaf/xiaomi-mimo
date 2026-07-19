<?php

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

test('API resources declare their model contract and generic payload type', function (string $resource, string $model) {
    expect(File::get(app_path("Http/Resources/{$resource}.php")))
        ->toContain("use App\\Models\\{$model};")
        ->toContain("/** @mixin {$model} */")
        ->toContain('/** @return array<string, mixed> */');
})->with([
    'activity log' => ['ActivityLogResource', 'ActivityLog'],
    'attachment' => ['AttachmentResource', 'Attachment'],
    'checklist item' => ['ChecklistItemResource', 'ChecklistItem'],
    'checklist' => ['ChecklistResource', 'Checklist'],
    'comment' => ['CommentResource', 'Comment'],
    'label' => ['LabelResource', 'Label'],
    'project' => ['ProjectResource', 'Project'],
    'reminder' => ['ReminderResource', 'Reminder'],
    'tag' => ['TagResource', 'Tag'],
    'todo' => ['TodoResource', 'Todo'],
    'user' => ['UserResource', 'User'],
    'workspace' => ['WorkspaceResource', 'Workspace'],
]);

test('user resource preserves the nullable avatar field', function () {
    $payload = (new UserResource(User::factory()->create()))
        ->resolve(Request::create('/api/user'));

    expect($payload)
        ->toHaveKey('avatar')
        ->and($payload['avatar'])->toBeNull();
});
