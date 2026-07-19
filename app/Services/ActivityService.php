<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    /** @param array<string, mixed>|null $properties */
    public function log(Model $subject, string $event, ?string $userId = null, ?string $workspaceId = null, ?array $properties = null): ActivityLog
    {
        $subjectWorkspaceId = $subject->getAttribute('workspace_id');

        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'workspace_id' => $workspaceId ?? (is_string($subjectWorkspaceId) ? $subjectWorkspaceId : null),
            'subject_type' => get_class($subject),
            'subject_id' => (string) $subject->getKey(),
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    /** @return Collection<int, ActivityLog> */
    public function getTimeline(string $workspaceId, int $limit = 50): Collection
    {
        return ActivityLog::where('workspace_id', $workspaceId)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
