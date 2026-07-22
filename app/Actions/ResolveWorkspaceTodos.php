<?php

namespace App\Actions;

use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ResolveWorkspaceTodos
{
    /**
     * @param  list<string>  $todoIds
     * @return Collection<int, Todo>
     */
    public function handle(Workspace $workspace, array $todoIds): Collection
    {
        $uniqueIds = collect($todoIds)->unique()->values();
        $todos = $workspace->todos()
            ->whereIn('id', $uniqueIds)
            ->lockForUpdate()
            ->get();

        if ($uniqueIds->count() !== count($todoIds) || $todos->count() !== $uniqueIds->count()) {
            throw ValidationException::withMessages([
                'ids' => __('validation.exists', ['attribute' => 'ids']),
            ]);
        }

        return $todos;
    }
}
