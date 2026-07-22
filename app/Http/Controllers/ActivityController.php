<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Queries\ActivityIndexQuery;
use App\Queries\CurrentWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function current(
        Request $request,
        CurrentWorkspaceQuery $currentWorkspaceQuery,
        ActivityIndexQuery $activityIndexQuery,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        return $this->render($workspace, $activityIndexQuery);
    }

    public function index(
        Workspace $workspace,
        ActivityIndexQuery $activityIndexQuery,
    ): Response {
        return $this->render($workspace, $activityIndexQuery);
    }

    private function render(?Workspace $workspace, ActivityIndexQuery $activityIndexQuery): Response
    {
        if (! $workspace) {
            return Inertia::render('activity/Index', [
                'activities' => ['data' => []],
            ]);
        }

        $this->authorize('view', $workspace);

        return Inertia::render('activity/Index', [
            'activities' => $activityIndexQuery->forWorkspace($workspace),
        ]);
    }
}
