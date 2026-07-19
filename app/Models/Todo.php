<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property Carbon|null $due_date
 * @property Carbon|null $start_date
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
    /** @use HasFactory<TodoFactory> */
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
            'due_date' => 'date:Y-m-d',
            'start_date' => 'date',
            'completed_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Todo, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'parent_id');
    }

    /** @return HasMany<Todo, $this> */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Todo::class, 'parent_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Checklist, $this> */
    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    /** @return HasMany<Reminder, $this> */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'todo_label');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'todo_tag');
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', TodoStatus::Completed);
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeCompletedToday(Builder $query): Builder
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
