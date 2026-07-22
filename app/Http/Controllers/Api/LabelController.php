<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateLabel;
use App\Actions\DeleteLabel;
use App\Actions\SyncTodoLabel;
use App\Actions\UpdateLabel;
use App\Http\Controllers\Controller;
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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabelController extends Controller
{
    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        return LabelResource::collection(
            $workspace->labels()
                ->with('workspace')
                ->withCount('todos')
                ->orderBy('name')
                ->limit(Label::MAX_PER_WORKSPACE)
                ->get(),
        );
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

    public function legacyUpdate(
        StoreLabelRequest $request,
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

    public function legacyDestroy(
        DeleteLabelRequest $request,
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
        SyncTodoLabel $action,
    ): JsonResponse {
        $label = $workspace->labels()->findOrFail($request->labelId());
        $action->attach($todo, $label);

        return response()->json(['label' => new LabelResource($label)]);
    }

    public function detach(
        DetachLabelRequest $request,
        Workspace $workspace,
        Todo $todo,
        Label $label,
        SyncTodoLabel $action,
    ): JsonResponse {
        $action->detach($todo, $label);

        return response()->json(null, 204);
    }
}
