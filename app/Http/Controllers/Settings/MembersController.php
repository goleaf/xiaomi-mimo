<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\CurrentWorkspaceQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembersController extends Controller
{
    public function edit(Request $request, CurrentWorkspaceQuery $currentWorkspaceQuery): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return to_route('workspaces.index');
        }

        return to_route('workspaces.members', $workspace);
    }
}
