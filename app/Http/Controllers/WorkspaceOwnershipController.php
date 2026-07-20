<?php

namespace App\Http\Controllers;

use App\Actions\TransferWorkspaceOwnership;
use App\Http\Requests\TransferWorkspaceOwnershipRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class WorkspaceOwnershipController extends Controller
{
    public function __invoke(
        TransferWorkspaceOwnershipRequest $request,
        Workspace $workspace,
        TransferWorkspaceOwnership $action,
    ): JsonResponse|RedirectResponse {
        $workspace = $action->handle($workspace, $request->user(), $request->newOwner())
            ->load('owner:id,name,email')
            ->loadCount(['members', 'projects', 'todos']);

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['workspace' => new WorkspaceResource($workspace)]);
        }

        return to_route('workspaces.members', $workspace);
    }
}
