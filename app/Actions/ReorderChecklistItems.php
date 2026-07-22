<?php

namespace App\Actions;

use App\Models\Checklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderChecklistItems
{
    /** @param list<string> $itemIds */
    public function handle(Checklist $checklist, array $itemIds): void
    {
        DB::transaction(function () use ($checklist, $itemIds): void {
            $items = $checklist->items()->lockForUpdate()->get();

            if ($items->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'ids' => __('validation.exists', ['attribute' => 'ids']),
                ]);
            }

            foreach ($itemIds as $position => $itemId) {
                $items->firstWhere('id', $itemId)?->update([
                    'position' => $position + 1,
                ]);
            }
        }, 5);
    }
}
