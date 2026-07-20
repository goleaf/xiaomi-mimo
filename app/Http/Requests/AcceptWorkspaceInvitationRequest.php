<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Http\FormRequest;

class AcceptWorkspaceInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }

    public function invitation(): WorkspaceInvitation
    {
        $invitation = $this->route('invitation');

        return WorkspaceInvitation::query()
            ->whereKey($invitation instanceof WorkspaceInvitation ? $invitation->id : $invitation)
            ->firstOrFail();
    }

    public function token(): string
    {
        return $this->string('token')->toString();
    }
}
