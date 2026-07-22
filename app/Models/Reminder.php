<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $reminded_at
 * @property ReminderType $type
 * @property ReminderStatus $status
 * @property string|null $claim_token
 * @property int $attempts
 * @property Carbon|null $claimed_at
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $last_error
 */
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory, HasUuid;

    public const int MAX_ATTEMPTS = 3;

    public const int CLAIM_LEASE_MINUTES = 10;

    protected $fillable = [
        'todo_id', 'user_id', 'reminded_at', 'is_sent', 'type', 'status',
        'claim_token', 'attempts', 'claimed_at', 'next_attempt_at',
        'delivered_at', 'failed_at', 'cancelled_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'reminded_at' => 'datetime',
            'is_sent' => 'boolean',
            'type' => ReminderType::class,
            'status' => ReminderStatus::class,
            'attempts' => 'integer',
            'claimed_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
