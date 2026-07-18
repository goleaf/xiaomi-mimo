<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\ReminderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasUuid;

    protected $fillable = ['todo_id', 'user_id', 'reminded_at', 'is_sent', 'type'];

    protected function casts(): array
    {
        return [
            'reminded_at' => 'datetime',
            'is_sent' => 'boolean',
            'type' => ReminderType::class,
        ];
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
