<?php

namespace App\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWorkspace
{
    public function __construct(
        private EnsureWorkspaceTaskDefinitions $ensureTaskDefinitions,
    ) {}

    /** @param array{name: string, description?: string|null} $data */
    public function handle(array $data, User $user): Workspace
    {
        return DB::transaction(function () use ($data, $user): Workspace {
            $workspace = Workspace::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::random(5),
                'description' => $data['description'] ?? null,
                'owner_id' => $user->id,
            ]);

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
            ]);
            $this->ensureTaskDefinitions->handle($workspace);

            return $workspace->load('members');
        }, 5);
    }
}
