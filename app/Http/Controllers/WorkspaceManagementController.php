<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskPriorityResource;
use App\Http\Resources\TaskStatusResource;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Http\Resources\WorkspaceMemberResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\WorkspaceManagementQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceManagementController extends Controller
{
    public function __construct(private WorkspaceManagementQuery $query) {}

    public function show(Request $request, Workspace $workspace): Response
    {
        return $this->render($request, $workspace, 'overview');
    }

    public function members(Request $request, Workspace $workspace): Response
    {
        return $this->render($request, $workspace, 'members');
    }

    public function configuration(Request $request, Workspace $workspace): Response
    {
        return $this->render($request, $workspace, 'configuration');
    }

    public function danger(Request $request, Workspace $workspace): Response
    {
        return $this->render($request, $workspace, 'danger');
    }

    private function render(Request $request, Workspace $workspace, string $section): Response
    {
        $this->authorize('view', $workspace);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $workspace = $this->query->workspace($workspace);

        return Inertia::render('workspaces/Show', [
            'section' => $section,
            'workspace' => (new WorkspaceResource($workspace))->resolve($request),
            'members' => WorkspaceMemberResource::collection($this->query->members($workspace))->resolve($request),
            'invitations' => WorkspaceInvitationResource::collection(
                $this->query->invitations($workspace, $user),
            )->resolve($request),
            'labels' => $section === 'configuration'
                ? LabelResource::collection($this->query->labels($workspace))->resolve($request)
                : [],
            'tags' => $section === 'configuration'
                ? TagResource::collection($this->query->tags($workspace))->resolve($request)
                : [],
            'taskStatuses' => $section === 'configuration'
                ? TaskStatusResource::collection($this->query->taskStatuses($workspace))->resolve($request)
                : [],
            'taskPriorities' => $section === 'configuration'
                ? TaskPriorityResource::collection($this->query->taskPriorities($workspace))->resolve($request)
                : [],
            'locale' => app()->getLocale(),
        ]);
    }
}
