<?php

namespace App\Actions;

use App\Models\Checklist;
use App\Models\ChecklistItem;

class CreateChecklistItem
{
    public function handle(Checklist $checklist, string $content): ChecklistItem
    {
        $maxPosition = $checklist->items()->max('position') ?? 0;

        return $checklist->items()->create([
            'content' => $content,
            'position' => $maxPosition + 1,
        ]);
    }
}
