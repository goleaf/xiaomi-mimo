<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkDeleteTodos
{
    public function __construct(private ResolveWorkspaceTodos $resolveTodos) {}

    /** @param list<string> $todoIds */
    public function handle(Workspace $workspace, array $todoIds): int
    {
        return DB::transaction(function () use ($workspace, $todoIds): int {
            $todos = $this->resolveTodos->handle($workspace, $todoIds);

            foreach ($todos as $todo) {
                $todo->delete();
            }

            return $todos->count();
        }, 5);
    }
}
