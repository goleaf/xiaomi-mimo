<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateChecklist;
use App\Actions\CreateChecklistItem;
use App\Actions\ToggleChecklistItem;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\ChecklistResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChecklistController extends Controller
{
    public function index(Todo $todo): AnonymousResourceCollection
    {
        $this->authorize('view', $todo);

        return ChecklistResource::collection($todo->checklists()->with('items')->get());
    }

    public function store(Request $request, Todo $todo, CreateChecklist $action): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $checklist = $action->handle($todo, $request->name);

        return response()->json(['checklist' => new ChecklistResource($checklist)], 201);
    }

    public function storeItem(Request $request, Checklist $checklist, CreateChecklistItem $action): JsonResponse
    {
        $request->validate(['content' => 'required|string|max:500']);
        $item = $action->handle($checklist, $request->content);

        return response()->json(['item' => new ChecklistItemResource($item)], 201);
    }

    public function toggleItem(ChecklistItem $item, ToggleChecklistItem $action): JsonResponse
    {
        $item = $action->handle($item);

        return response()->json(['item' => new ChecklistItemResource($item)]);
    }
}
