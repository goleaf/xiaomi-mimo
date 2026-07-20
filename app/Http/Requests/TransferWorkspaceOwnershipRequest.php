<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferWorkspaceOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('transferOwnership', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                Rule::exists(WorkspaceMember::class, 'user_id')
                    ->where('workspace_id', $this->workspace()->id),
            ],
        ];
    }

    public function newOwner(): User
    {
        return $this->workspace()->members()
            ->whereKey($this->string('user_id')->toString())
            ->firstOrFail();
    }

    private function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        abort_unless($workspace instanceof Workspace, 404);

        return $workspace;
    }
}
