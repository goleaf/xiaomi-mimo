<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $project_id
 * @property string $workspace_id
 * @property string|null $assigned_to
 * @property string|null $parent_id
 * @property string $title
 * @property string|null $description
 * @property TodoStatus $status
 * @property TodoPriority $priority
 * @property string|null $due_date
 * @property string|null $start_date
 * @property int|null $estimated_time
 * @property int|null $spent_time
 * @property bool $is_pinned
 * @property bool $is_favorite
 * @property bool $is_archived
 * @property bool $is_recurring
 * @property string|null $recurring_rule
 * @property int $position
 * @property Carbon|null $completed_at
 */
class Todo extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'project_id', 'workspace_id', 'assigned_to', 'parent_id',
        'title', 'description', 'status', 'priority',
        'due_date', 'start_date', 'estimated_time', 'spent_time',
        'is_pinned', 'is_favorite', 'is_archived', 'is_recurring',
        'recurring_rule', 'position', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TodoStatus::class,
            'priority' => TodoPriority::class,
            'due_date' => 'date',
            'start_date' => 'date',
            'completed_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Todo::class, 'parent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForWorkspace($query, string $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', TodoStatus::Completed);
    }

    public function scopeCompletedToday($query)
    {
        return $query->whereDate('completed_at', now()->toDateString());
    }

    public function getProgressAttribute(): float
    {
        $total = $this->checklists()->sum(
            \DB::raw('(SELECT COUNT(*) FROM checklist_items WHERE checklist_id = checklists.id)')
        );
        $checked = $this->checklists()->sum(
            \DB::raw('(SELECT COUNT(*) FROM checklist_items WHERE checklist_id = checklists.id AND is_checked = 1)')
        );

        return $total > 0 ? round(($checked / $total) * 100) : 0;
    }
}
