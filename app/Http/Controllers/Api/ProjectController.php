<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateProject;
use App\Actions\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        return ProjectResource::collection($workspace->projects()->withCount('todos')->get());
    }

    public function store(StoreProjectRequest $request, Workspace $workspace, CreateProject $action): JsonResponse
    {
        $this->authorize('create', [Project::class, $workspace]);
        $project = $action->handle($workspace, $request->projectData());

        return response()->json(['project' => new ProjectResource($project)], 201);
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->loadCount('todos'));
    }

    public function showScoped(Workspace $workspace, Project $project): ProjectResource
    {
        return $this->show($project);
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $action): JsonResponse
    {
        $project = $action->handle($project, $request->validated());

        return response()->json(['project' => new ProjectResource($project)]);
    }

    public function updateScoped(
        UpdateProjectRequest $request,
        Workspace $workspace,
        Project $project,
        UpdateProject $action,
    ): JsonResponse {
        return $this->update($request, $project, $action);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json(null, 204);
    }

    public function destroyScoped(Workspace $workspace, Project $project): JsonResponse
    {
        return $this->destroy($project);
    }
}
