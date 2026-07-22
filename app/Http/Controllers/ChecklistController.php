<?php

namespace App\Http\Controllers;

use App\Actions\CreateChecklist;
use App\Actions\CreateChecklistItem;
use App\Actions\ToggleChecklistItem;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function store(
        Request $request,
        Todo $todo,
        CreateChecklist $action,
    ): RedirectResponse {
        $this->authorize('update', $todo);
        $request->validate(['name' => 'required|string|max:255']);
        $action->handle($todo, $request->name);

        return back();
    }

    public function update(Request $request, Checklist $checklist): RedirectResponse
    {
        $this->authorize('update', $checklist->todo);
        $request->validate(['name' => 'required|string|max:255']);
        $checklist->update(['name' => $request->name]);

        return back();
    }

    public function destroy(Checklist $checklist): RedirectResponse
    {
        $this->authorize('update', $checklist->todo);
        $checklist->delete();

        return back();
    }

    public function storeItem(
        Request $request,
        Checklist $checklist,
        CreateChecklistItem $action,
    ): RedirectResponse {
        $this->authorize('update', $checklist->todo);
        $request->validate(['content' => 'required|string|max:500']);
        $action->handle($checklist, $request->content);

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

    public function destroyItem(ChecklistItem $item): RedirectResponse
    {
        $this->authorize('update', $item->checklist->todo);
        $item->delete();

        return back();
    }
}
