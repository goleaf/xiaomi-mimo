<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['checklist_id', 'content', 'is_checked', 'position'];

    protected function casts(): array
    {
        return ['is_checked' => 'boolean'];
    }

    /** @return BelongsTo<Checklist, $this> */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}
