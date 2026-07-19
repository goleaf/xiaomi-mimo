<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property string $icon
 * @property bool $is_archived
 * @property int $position
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['workspace_id', 'name', 'description', 'color', 'icon', 'is_archived', 'position'];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }
}
