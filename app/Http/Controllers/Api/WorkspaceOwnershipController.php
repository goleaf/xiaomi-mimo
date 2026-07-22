<?php

namespace App\Http\Controllers\Api;

use App\Actions\TransferWorkspaceOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferWorkspaceOwnershipRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class WorkspaceOwnershipController extends Controller
{
    public function __invoke(
        TransferWorkspaceOwnershipRequest $request,
        Workspace $workspace,
        TransferWorkspaceOwnership $action,
    ): JsonResponse {
        $workspace = $action->handle($workspace, $request->user(), $request->newOwner())
            ->load('owner:id,name,email')
            ->loadCount(['members', 'projects', 'todos']);

        return response()->json(['workspace' => new WorkspaceResource($workspace)]);
    }
}
