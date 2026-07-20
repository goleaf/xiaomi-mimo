<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $owner_id
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['name', 'slug', 'description', 'owner_id'];

    /** @var array<string, string|null> */
    private array $resolvedMemberRoles = [];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('position');
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /** @return HasMany<Label, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->memberRole($user) !== null;
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function memberRole(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return WorkspaceRole::Owner->value;
        }

        if (array_key_exists($user->id, $this->resolvedMemberRoles)) {
            return $this->resolvedMemberRoles[$user->id];
        }

        if ($this->relationLoaded('pivot')) {
            $pivot = $this->getRelation('pivot');

            if ($pivot instanceof Pivot && $pivot->getAttribute('user_id') === $user->id) {
                $role = $pivot->getAttribute('role');

                return $this->resolvedMemberRoles[$user->id] = is_string($role) ? $role : null;
            }
        }

        if ($this->relationLoaded('members')) {
            $member = $this->members->firstWhere('id', $user->id);
            $pivot = $member?->getRelation('pivot');
            $role = $pivot instanceof Pivot ? $pivot->getAttribute('role') : null;

            return $this->resolvedMemberRoles[$user->id] = is_string($role) ? $role : null;
        }

        $role = $this->members()->whereKey($user->id)->first()?->pivot->getAttribute('role');

        return $this->resolvedMemberRoles[$user->id] = is_string($role) ? $role : null;
    }
}
