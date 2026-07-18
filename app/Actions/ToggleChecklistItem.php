<?php

namespace App\Actions;

use App\Models\ChecklistItem;

class ToggleChecklistItem
{
    public function handle(ChecklistItem $item): ChecklistItem
    {
        $item->update(['is_checked' => ! $item->is_checked]);

        return $item->fresh();
    }
}
