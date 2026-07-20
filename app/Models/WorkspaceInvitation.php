<?php

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceInvitationFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string|null $invited_by
 * @property string $email
 * @property WorkspaceRole $role
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Workspace $workspace
 */
class WorkspaceInvitation extends Model
{
    /** @use HasFactory<WorkspaceInvitationFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'workspace_id',
        'invited_by',
        'email',
        'role',
        'token_hash',
        'expires_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isFuture();
    }

    public function hasToken(string $token): bool
    {
        return hash_equals($this->token_hash, hash('sha256', $token));
    }

    public function acceptUrl(string $token, ?DateTimeInterface $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'workspace-invitations.accept',
            $expiration ?? $this->expires_at,
            ['invitation' => $this, 'token' => $token],
        );
    }
}
