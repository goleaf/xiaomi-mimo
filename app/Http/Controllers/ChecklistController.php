<?php

namespace App\Http\Controllers;

use App\Actions\CreateChecklist;
use App\Actions\CreateChecklistItem;
use App\Actions\ManageChecklist;
use App\Actions\ManageChecklistItem;
use App\Actions\ToggleChecklistItem;
use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\StoreChecklistRequest;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use Illuminate\Http\RedirectResponse;

class ChecklistController extends Controller
{
    public function store(
        StoreChecklistRequest $request,
        Todo $todo,
        CreateChecklist $action,
    ): RedirectResponse {
        $action->handle($todo, $request->name());

        return back();
    }

    public function update(
        StoreChecklistRequest $request,
        Checklist $checklist,
        ManageChecklist $action,
    ): RedirectResponse {
        $action->update($checklist, $request->name());

        return back();
    }

    public function destroy(Checklist $checklist, ManageChecklist $action): RedirectResponse
    {
        $this->authorize('update', $checklist->todo);
        $action->delete($checklist);

        return back();
    }

    public function storeItem(
        StoreChecklistItemRequest $request,
        Checklist $checklist,
        CreateChecklistItem $action,
    ): RedirectResponse {
        $action->handle($checklist, $request->content());

        return back();
    }

    public function toggleItem(
        ChecklistItem $item,
        ToggleChecklistItem $action,
    ): RedirectResponse {
        $this->authorize('update', $item->checklist->todo);
        $action->handle($item);

        return back();
    }

    public function updateItem(
        StoreChecklistItemRequest $request,
        ChecklistItem $item,
        ManageChecklistItem $action,
    ): RedirectResponse {
        $action->update($item, $request->content());

        return back();
    }

    public function destroyItem(ChecklistItem $item, ManageChecklistItem $action): RedirectResponse
    {
        $this->authorize('update', $item->checklist->todo);
        $action->delete($item);

        return back();
    }
}
