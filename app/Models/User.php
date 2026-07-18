<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 */
class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, HasUuid, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function currentWorkspace(): ?Workspace
    {
        return $this->workspaces()->first();
    }
}
