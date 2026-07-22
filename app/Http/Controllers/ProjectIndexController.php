<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\CurrentWorkspaceQuery;
use App\Queries\ProjectIndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIndexController extends Controller
{
    public function current(
        Request $request,
        CurrentWorkspaceQuery $currentWorkspaceQuery,
        ProjectIndexQuery $projectIndexQuery,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        return $this->render($request, $workspace, $projectIndexQuery);
    }

    public function workspace(
        Request $request,
        Workspace $workspace,
        ProjectIndexQuery $projectIndexQuery,
    ): Response {
        return $this->render($request, $workspace, $projectIndexQuery);
    }

    private function render(
        Request $request,
        ?Workspace $workspace,
        ProjectIndexQuery $projectIndexQuery,
    ): Response {
        if (! $workspace) {
            return Inertia::render('projects/Index', [
                'projects' => ['data' => []],
                'workspace' => ['id' => '', 'name' => ''],
            ]);
        }

        $this->authorize('view', $workspace);

        return Inertia::render('projects/Index', [
            'projects' => ProjectResource::collection($projectIndexQuery->forWorkspace($workspace)),
            'workspace' => $workspace,
        ]);
    }
}
