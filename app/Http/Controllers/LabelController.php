<?php

namespace App\Http\Controllers;

use App\Actions\CreateLabel;
use App\Actions\DeleteLabel;
use App\Actions\UpdateLabel;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $labels = $workspace->labels()->withCount('todos')->get();

        return response()->json(['labels' => LabelResource::collection($labels)]);
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

    public function attach(Request $request, Todo $todo): JsonResponse
    {
        $request->validate(['label_id' => 'required|uuid|exists:labels,id']);
        $todo->labels()->syncWithoutDetaching([$request->label_id]);

        return response()->json(null, 204);
    }

    public function detach(Todo $todo, Label $label): JsonResponse
    {
        $todo->labels()->detach($label->id);

        return response()->json(null, 204);
    }
}
