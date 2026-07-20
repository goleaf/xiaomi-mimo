<?php

namespace App\Queries;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceManagementQuery
{
    public function workspace(Workspace $workspace): Workspace
    {
        return $workspace->load('owner:id,name,email')
            ->loadCount(['members', 'projects', 'todos']);
    }

    /** @return Collection<int, WorkspaceMember> */
    public function members(Workspace $workspace): Collection
    {
        return $workspace->memberships()
            ->with(['user:id,name,email', 'workspace'])
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->oldest()
            ->get();
    }

    /** @return Collection<int, WorkspaceInvitation> */
    public function invitations(Workspace $workspace, User $viewer): Collection
    {
        if (! $viewer->can('manageMembers', $workspace)) {
            return new Collection;
        }

        return $workspace->invitations()
            ->with('workspace')
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->latest()
            ->get();
    }
}
