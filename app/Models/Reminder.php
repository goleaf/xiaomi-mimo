<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\ReminderType;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $reminded_at
 * @property ReminderType $type
 */
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['todo_id', 'user_id', 'reminded_at', 'is_sent', 'type'];

    protected function casts(): array
    {
        return [
            'reminded_at' => 'datetime',
            'is_sent' => 'boolean',
            'type' => ReminderType::class,
        ];
    }

    /** @return BelongsTo<Todo, $this> */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
