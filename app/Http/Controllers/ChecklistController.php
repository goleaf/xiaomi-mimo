<?php

namespace App\Http\Controllers;

use App\Actions\CreateChecklist;
use App\Actions\CreateChecklistItem;
use App\Actions\ToggleChecklistItem;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\ChecklistResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function store(Request $request, Todo $todo, CreateChecklist $action): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $checklist = $action->handle($todo, $request->name);

        return response()->json(['checklist' => new ChecklistResource($checklist)], 201);
    }

    public function update(Request $request, Checklist $checklist): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $checklist->update(['name' => $request->name]);

        return response()->json(['checklist' => new ChecklistResource($checklist->fresh('items'))]);
    }

    public function destroy(Checklist $checklist): JsonResponse
    {
        $checklist->delete();

        return response()->json(null, 204);
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

    public function destroyItem(ChecklistItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
