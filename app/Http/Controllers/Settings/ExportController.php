<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\CurrentWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExportController extends Controller
{
    public function edit(Request $request, CurrentWorkspaceQuery $currentWorkspaceQuery): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        return Inertia::render('settings/Export', [
            'workspace' => $workspace,
        ]);
    }
}
