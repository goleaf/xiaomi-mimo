<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateChecklist;
use App\Actions\CreateChecklistItem;
use App\Actions\ManageChecklist;
use App\Actions\ManageChecklistItem;
use App\Actions\ReorderChecklistItems;
use App\Actions\ReorderChecklists;
use App\Actions\ToggleChecklistItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderChecklistItemsRequest;
use App\Http\Requests\ReorderChecklistsRequest;
use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\StoreChecklistRequest;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\ChecklistResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChecklistController extends Controller
{
    public function index(Todo $todo): AnonymousResourceCollection
    {
        $this->authorize('view', $todo);

        return ChecklistResource::collection($todo->checklists()->with('items')->get());
    }

    public function store(StoreChecklistRequest $request, Todo $todo, CreateChecklist $action): JsonResponse
    {
        $checklist = $action->handle($todo, $request->name())->load('items');

        return response()->json(['checklist' => new ChecklistResource($checklist)], 201);
    }

    public function update(
        StoreChecklistRequest $request,
        Todo $todo,
        Checklist $checklist,
        ManageChecklist $action,
    ): JsonResponse {
        $checklist = $action->update($checklist, $request->name());

        return response()->json(['checklist' => new ChecklistResource($checklist)]);
    }

    public function destroy(Todo $todo, Checklist $checklist, ManageChecklist $action): JsonResponse
    {
        $this->authorize('update', $todo);
        $action->delete($checklist);

        return response()->json(null, 204);
    }

    public function reorder(
        ReorderChecklistsRequest $request,
        Todo $todo,
        ReorderChecklists $action,
    ): JsonResponse {
        $action->handle($todo, $request->ids());

        return response()->json(null, 204);
    }

    public function storeItem(
        StoreChecklistItemRequest $request,
        Checklist $checklist,
        CreateChecklistItem $action,
    ): JsonResponse {
        $item = $action->handle($checklist, $request->content());

        return response()->json(['item' => new ChecklistItemResource($item)], 201);
    }

    public function storeItemScoped(
        StoreChecklistItemRequest $request,
        Todo $todo,
        Checklist $checklist,
        CreateChecklistItem $action,
    ): JsonResponse {
        return $this->storeItem($request, $checklist, $action);
    }

    public function updateItem(
        StoreChecklistItemRequest $request,
        Todo $todo,
        Checklist $checklist,
        ChecklistItem $item,
        ManageChecklistItem $action,
    ): JsonResponse {
        $item = $action->update($item, $request->content());

        return response()->json(['item' => new ChecklistItemResource($item)]);
    }

    public function destroyItem(
        Todo $todo,
        Checklist $checklist,
        ChecklistItem $item,
        ManageChecklistItem $action,
    ): JsonResponse {
        $this->authorize('update', $todo);
        $action->delete($item);

        return response()->json(null, 204);
    }

    public function reorderItems(
        ReorderChecklistItemsRequest $request,
        Todo $todo,
        Checklist $checklist,
        ReorderChecklistItems $action,
    ): JsonResponse {
        $action->handle($checklist, $request->ids());

        return response()->json(null, 204);
    }

    public function toggleItem(ChecklistItem $item, ToggleChecklistItem $action): JsonResponse
    {
        $this->authorize('update', $item->checklist->todo);
        $item = $action->handle($item);

        return response()->json(['item' => new ChecklistItemResource($item)]);
    }

    public function toggleItemScoped(
        Todo $todo,
        Checklist $checklist,
        ChecklistItem $item,
        ToggleChecklistItem $action,
    ): JsonResponse {
        return $this->toggleItem($item, $action);
    }
}
