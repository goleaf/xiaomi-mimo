<?php

namespace App\Queries;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ProjectIndexQuery
{
    /** @return Collection<int, Project> */
    public function forWorkspace(Workspace $workspace): Collection
    {
        return $workspace->projects()
            ->withCount('todos')
            ->get();
    }
}
