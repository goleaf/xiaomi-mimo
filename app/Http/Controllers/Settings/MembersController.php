<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembersController extends Controller
{
    public function edit(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return to_route('workspaces.index');
        }

        return to_route('workspaces.members', $workspace);
    }
}
