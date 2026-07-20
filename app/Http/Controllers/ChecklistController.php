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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function store(
        Request $request,
        Todo $todo,
        CreateChecklist $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $todo);
        $request->validate(['name' => 'required|string|max:255']);
        $checklist = $action->handle($todo, $request->name);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['checklist' => new ChecklistResource($checklist)], 201);
    }

    public function update(Request $request, Checklist $checklist): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $checklist->todo);
        $request->validate(['name' => 'required|string|max:255']);
        $checklist->update(['name' => $request->name]);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['checklist' => new ChecklistResource($checklist->fresh('items'))]);
    }

    public function destroy(Request $request, Checklist $checklist): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $checklist->todo);
        $checklist->delete();

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }

    public function storeItem(
        Request $request,
        Checklist $checklist,
        CreateChecklistItem $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $checklist->todo);
        $request->validate(['content' => 'required|string|max:500']);
        $item = $action->handle($checklist, $request->content);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['item' => new ChecklistItemResource($item)], 201);
    }

    public function toggleItem(
        Request $request,
        ChecklistItem $item,
        ToggleChecklistItem $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $item->checklist->todo);
        $item = $action->handle($item);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['item' => new ChecklistItemResource($item)]);
    }

    public function destroyItem(Request $request, ChecklistItem $item): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $item->checklist->todo);
        $item->delete();

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }
}
