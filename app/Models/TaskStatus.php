<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\TaskStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    public const int MAX_PER_WORKSPACE = 50;

    /** @use HasFactory<TaskStatusFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'workspace_id', 'key', 'name', 'translation_key', 'color', 'position',
        'is_default', 'is_completed', 'is_completion_target', 'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_completion_target' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }

    protected static function booted(): void
    {
        static::saving(function (TaskStatus $status): void {
            if ($status->isDirty('name')) {
                $status->normalized_name = self::normalizeName($status->name);
            }
        });
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'status_id');
    }

    /** @return HasMany<Todo, $this> */
    public function allTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'status_id')->withTrashed();
    }

    /** @param Builder<TaskStatus> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_archived', false);
    }

    /** @param Builder<TaskStatus> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }
}
