<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'workspace_id', 'subject_type', 'subject_id', 'event', 'properties'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForWorkspace($query, string $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
