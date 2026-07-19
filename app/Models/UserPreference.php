<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id', 'timezone', 'language', 'date_format', 'time_format',
        'theme', 'default_view', 'start_page',
        'notification_email', 'notification_browser', 'notification_in_app',
    ];

    protected function casts(): array
    {
        return [
            'notification_email' => 'boolean',
            'notification_browser' => 'boolean',
            'notification_in_app' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
