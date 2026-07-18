<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

class InviteToWorkspace
{
    public function handle(Workspace $workspace, string $email, string $role = 'member'): WorkspaceMember
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $email, 'password' => bcrypt('password')]
        );

        return WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }
}
