<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceInvitation */
class WorkspaceInvitationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $role = $this->role;
        $canWriteThroughApi = ! $request->is('api/*')
            || ($viewer instanceof User && $viewer->tokenCan('workspaces:write'));

        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $role->value,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->expires_at->isPast(),
            'created_at' => $this->created_at,
            'permissions' => [
                'resend' => $canWriteThroughApi && ($viewer?->can('resend', $this->resource) ?? false),
                'cancel' => $canWriteThroughApi && ($viewer?->can('cancel', $this->resource) ?? false),
            ],
        ];
    }
}
