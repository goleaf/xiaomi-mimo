<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['todo_id', 'name', 'position'];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function getProgressAttribute(): float
    {
        $total = $this->items()->count();
        $checked = $this->items()->where('is_checked', true)->count();

        return $total > 0 ? round(($checked / $total) * 100) : 0;
    }
}
