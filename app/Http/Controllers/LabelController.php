<?php

namespace App\Http\Controllers;

use App\Actions\CreateLabel;
use App\Actions\DeleteLabel;
use App\Actions\UpdateLabel;
use App\Http\Requests\AttachLabelRequest;
use App\Http\Requests\DeleteLabelRequest;
use App\Http\Requests\DetachLabelRequest;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);
        $labels = $workspace->labels()
            ->with('workspace')
            ->withCount('todos')
            ->orderBy('name')
            ->limit(Label::MAX_PER_WORKSPACE)
            ->get();

        return response()->json(['labels' => LabelResource::collection($labels)]);
    }

    public function store(StoreLabelRequest $request, Workspace $workspace, CreateLabel $action): JsonResponse
    {
        $label = $action->handle($workspace, $request->name(), $request->color());

        return response()->json(['label' => new LabelResource($label)], 201);
    }

    public function update(
        StoreLabelRequest $request,
        Workspace $workspace,
        Label $label,
        UpdateLabel $action,
    ): JsonResponse {
        $label = $action->handle($label, $request->validated());

        return response()->json(['label' => new LabelResource($label)]);
    }

    public function destroy(
        DeleteLabelRequest $request,
        Workspace $workspace,
        Label $label,
        DeleteLabel $action,
    ): JsonResponse {
        $action->handle($label);

        return response()->json(null, 204);
    }

    public function attach(
        AttachLabelRequest $request,
        Workspace $workspace,
        Todo $todo,
    ): JsonResponse {
        $todo->labels()->syncWithoutDetachingOrFail([$request->labelId()]);

        return response()->json(null, 204);
    }

    public function detach(
        DetachLabelRequest $request,
        Workspace $workspace,
        Todo $todo,
        Label $label,
    ): JsonResponse {
        $todo->labels()->detachOrFail($label->id);

        return response()->json(null, 204);
    }
}
