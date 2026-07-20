<?php

namespace Database\Seeders;

use App\Actions\EnsureWorkspaceTaskDefinitions;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'demo@example.com')->firstOrFail();
        $alice = User::where('email', 'alice@example.com')->firstOrFail();
        $bob = User::where('email', 'bob@example.com')->firstOrFail();

        $workspace = Workspace::create([
            'name' => 'Acme Projects',
            'slug' => 'acme-projects',
            'description' => 'Main workspace for all Acme product development and operations.',
            'owner_id' => $owner->id,
        ]);

        WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => WorkspaceRole::Owner]);
        WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $alice->id, 'role' => WorkspaceRole::Admin]);
        WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $bob->id, 'role' => WorkspaceRole::Member]);
        app(EnsureWorkspaceTaskDefinitions::class)->handle($workspace);

        $this->command->info('Created workspace "Acme Projects" with 3 members.');
    }
}
