<?php

namespace App\Queries;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityIndexQuery
{
    /** @return LengthAwarePaginator<int, ActivityLog> */
    public function forWorkspace(Workspace $workspace, int $perPage = 50): LengthAwarePaginator
    {
        return $workspace->activityLogs()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
