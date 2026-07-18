<?php

namespace App\Http\Controllers;

use App\Actions\ArchiveProject;
use App\Actions\CreateProject;
use App\Actions\DuplicateProject;
use App\Actions\UpdateProject;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        $this->authorize('view', $workspace);

        $projects = $workspace->projects()
            ->withCount('todos')
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'workspace' => $workspace,
        ]);
    }

    public function show(Request $request, Workspace $workspace, Project $project): Response
    {
        $this->authorize('view', $project);

        $todos = $project->todos()
            ->with(['assignee', 'labels', 'tags'])
            ->active()
            ->orderBy('position')
            ->get();

        return Inertia::render('projects/Show', [
            'project' => new ProjectResource($project),
            'todos' => $todos,
            'workspace' => ['id' => $workspace->id],
        ]);
    }

    public function store(StoreProjectRequest $request, Workspace $workspace, CreateProject $action): JsonResponse
    {
        $this->authorize('create', [Project::class, $workspace]);
        $project = $action->handle($workspace, $request->validated());

        return response()->json(['project' => new ProjectResource($project)], 201);
    }

    public function update(UpdateProjectRequest $request, Workspace $workspace, Project $project, UpdateProject $action): JsonResponse
    {
        $project = $action->handle($project, $request->validated());

        return response()->json(['project' => new ProjectResource($project)]);
    }

    public function destroy(Workspace $workspace, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json(null, 204);
    }

    public function archive(Workspace $workspace, Project $project, ArchiveProject $action): JsonResponse
    {
        $this->authorize('archive', $project);
        $project = $action->handle($project);

        return response()->json(['project' => new ProjectResource($project)]);
    }

    public function restore(Workspace $workspace, Project $project): JsonResponse
    {
        $this->authorize('archive', $project);
        $project->update(['is_archived' => false]);

        return response()->json(['project' => new ProjectResource($project->fresh())]);
    }

    public function duplicate(Workspace $workspace, Project $project, DuplicateProject $action): JsonResponse
    {
        $this->authorize('create', [Project::class, $workspace]);
        $project = $action->handle($project);

        return response()->json(['project' => new ProjectResource($project)], 201);
    }

    public function reorder(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        foreach ($request->items as $item) {
            $workspace->projects()->where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(null, 204);
    }
}
