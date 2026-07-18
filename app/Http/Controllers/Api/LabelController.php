<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateLabel;
use App\Actions\DeleteLabel;
use App\Actions\UpdateLabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabelController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        return LabelResource::collection($workspace->labels()->withCount('todos')->get());
    }

    public function store(StoreLabelRequest $request, Workspace $workspace, CreateLabel $action): JsonResponse
    {
        $label = $action->handle($workspace, $request->name, $request->color ?? '#6366f1');

        return response()->json(['label' => new LabelResource($label)], 201);
    }

    public function update(StoreLabelRequest $request, Label $label, UpdateLabel $action): JsonResponse
    {
        $label = $action->handle($label, $request->validated());

        return response()->json(['label' => new LabelResource($label)]);
    }

    public function destroy(Label $label, DeleteLabel $action): JsonResponse
    {
        $action->handle($label);

        return response()->json(null, 204);
    }
}
