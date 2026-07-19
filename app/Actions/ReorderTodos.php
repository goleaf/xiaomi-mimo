<?php

namespace App\Actions;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class ReorderTodos
{
    /** @param list<array{id: string, position: int}> $items */
    public function handle(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Todo::where('id', $item['id'])->update(['position' => $item['position']]);
            }
        });
    }
}
