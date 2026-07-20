<?php

namespace App\Actions;

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\Tag;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateWorkspace
{
    public function handle(Workspace $sourceWorkspace, User $owner, string $name): Workspace
    {
        return DB::transaction(function () use ($sourceWorkspace, $owner, $name): Workspace {
            $workspace = Workspace::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(5),
                'description' => $sourceWorkspace->description,
                'owner_id' => $owner->id,
            ]);

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $owner->id,
                'role' => WorkspaceRole::Owner,
            ]);

            $sourceWorkspace->labels()->get()->each(
                fn (Label $label) => $workspace->labels()->create($label->only(['name', 'color'])),
            );

            $sourceWorkspace->tags()->get()->each(
                fn (Tag $tag) => $workspace->tags()->create($tag->only(['name'])),
            );

            $sourceWorkspace->taskStatuses()->ordered()->get()->each(
                fn (TaskStatus $status) => $workspace->taskStatuses()->create($status->only([
                    'key', 'name', 'translation_key', 'color', 'position', 'is_default',
                    'is_completed', 'is_completion_target', 'is_archived',
                ])),
            );

            $sourceWorkspace->taskPriorities()->ordered()->get()->each(
                fn (TaskPriority $priority) => $workspace->taskPriorities()->create($priority->only([
                    'key', 'name', 'translation_key', 'color', 'position', 'is_default', 'is_archived',
                ])),
            );

            return $workspace;
        });
    }
}
