<?php

namespace App\Actions;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderChecklists
{
    /** @param list<string> $checklistIds */
    public function handle(Todo $todo, array $checklistIds): void
    {
        DB::transaction(function () use ($todo, $checklistIds): void {
            $checklists = $todo->checklists()->lockForUpdate()->get();

            if ($checklists->count() !== count($checklistIds)) {
                throw ValidationException::withMessages([
                    'ids' => __('validation.exists', ['attribute' => 'ids']),
                ]);
            }

            foreach ($checklistIds as $position => $checklistId) {
                $checklists->firstWhere('id', $checklistId)?->update([
                    'position' => $position + 1,
                ]);
            }
        }, 5);
    }
}
