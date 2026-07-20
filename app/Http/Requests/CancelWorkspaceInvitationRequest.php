<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Http\FormRequest;

class CancelWorkspaceInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('cancel', $this->invitation());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function invitation(): WorkspaceInvitation
    {
        $workspace = $this->route('workspace');
        $invitation = $this->route('invitation');

        abort_unless($workspace instanceof Workspace, 404);

        return $workspace->invitations()
            ->with('workspace')
            ->whereKey($invitation instanceof WorkspaceInvitation ? $invitation->id : $invitation)
            ->firstOrFail();
    }
}
