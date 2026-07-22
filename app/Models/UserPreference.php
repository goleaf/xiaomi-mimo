<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\UserPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<UserPreferenceFactory> */
    use HasFactory, HasUuid;

    public const array DATE_FORMATS = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd.m.Y'];

    public const array TIME_FORMATS = ['H:i', 'h:i A'];

    public const array DEFAULT_VIEWS = ['list', 'board', 'calendar'];

    public const array START_PAGE_ROUTES = [
        'dashboard' => 'dashboard',
        'tasks' => 'todos.index',
        'projects' => 'projects',
        'calendar' => 'calendar',
    ];

    public const array START_PAGES = ['dashboard', 'tasks', 'projects', 'calendar'];

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

    /**
     * @return array{timezone: string, language: string, date_format: string, time_format: string, theme: string, default_view: string, start_page: string, notification_email: bool, notification_browser: bool, notification_in_app: bool}
     */
    public static function defaults(): array
    {
        return [
            'timezone' => 'UTC',
            'language' => 'en',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'theme' => 'system',
            'default_view' => 'list',
            'start_page' => 'dashboard',
            'notification_email' => true,
            'notification_browser' => true,
            'notification_in_app' => true,
        ];
    }

    public static function startRoute(?string $startPage): string
    {
        return self::START_PAGE_ROUTES[$startPage ?? 'dashboard'] ?? 'dashboard';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
