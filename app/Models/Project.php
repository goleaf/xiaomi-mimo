<?php

namespace App\Models;

use App\Concerns\HasUuid;
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
    use HasFactory, HasUuid;

    protected $fillable = ['workspace_id', 'name', 'description', 'color', 'icon', 'is_archived', 'position'];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
