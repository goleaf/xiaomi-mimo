<?php

namespace App\Actions;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class LogActivity
{
    public function handle(Model $subject, string $event, ?string $userId = null, ?string $workspaceId = null, ?array $properties = null): ActivityLog
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
}
