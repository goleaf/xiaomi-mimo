<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class ReorderTodos
{
    /** @param list<array{id: string, position: int}> $items */
    public function handle(Workspace $workspace, array $items): void
    {
        DB::transaction(function () use ($workspace, $items): void {
            foreach ($items as $item) {
                $workspace->todos()->whereKey($item['id'])->update(['position' => $item['position']]);
            }
        }, 5);
    }
}
