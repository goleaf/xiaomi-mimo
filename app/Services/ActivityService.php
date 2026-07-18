<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    public function log(Model $subject, string $event, ?string $userId = null, ?string $workspaceId = null, ?array $properties = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'workspace_id' => $workspaceId ?? $subject->workspace_id ?? null,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    public function getTimeline(string $workspaceId, int $limit = 50): Collection
    {
        return ActivityLog::where('workspace_id', $workspaceId)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
