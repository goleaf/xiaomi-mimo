<?php

namespace App\Actions;

use App\Models\ChecklistItem;

class ManageChecklistItem
{
    public function update(ChecklistItem $item, string $content): ChecklistItem
    {
        $item->update(['content' => $content]);

        return $item->refresh();
    }

    public function delete(ChecklistItem $item): bool
    {
        return (bool) $item->delete();
    }
}
