<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasUuid;

    protected $fillable = ['checklist_id', 'content', 'is_checked', 'position'];

    protected function casts(): array
    {
        return ['is_checked' => 'boolean'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}
