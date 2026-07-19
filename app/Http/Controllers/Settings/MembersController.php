<?php

namespace App\Http\Controllers\Settings;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
    public function edit(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if ($workspace === null) {
            abort(404);
        }

        $canManageMembers = $request->user()->can('manageMembers', $workspace);
        $members = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->with('user:id,name,email')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->oldest()
            ->get()
            ->map(function (WorkspaceMember $membership) use ($canManageMembers, $request): array {
                $member = $membership->getRelation('user');
                $role = $membership->getAttribute('role');

                if (! $member instanceof User || ! $role instanceof WorkspaceRole) {
                    abort(500);
                }

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $role->value,
                    'is_current_user' => (string) $request->user()->getAuthIdentifier() === $member->id,
                    'can_remove' => $canManageMembers
                        && $role !== WorkspaceRole::Owner
                        && (string) $request->user()->getAuthIdentifier() !== $member->id,
                ];
            });

        return Inertia::render('settings/Members', [
            'workspace' => $workspace->only(['id', 'name', 'slug', 'owner_id']),
            'members' => $members,
            'can_manage_members' => $canManageMembers,
            'locale' => app()->getLocale(),
            'copy' => trans('members'),
        ]);
    }
}
