<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportWorkspaceRequest;
use App\Models\Workspace;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function import(
        ImportWorkspaceRequest $request,
        Workspace $workspace,
        ImportService $service,
    ): JsonResponse {
        $content = $request->uploadedFile()->getContent();

        $result = match ($request->importFormat()) {
            'json' => $service->importFromJson($workspace, $content),
            'csv' => ['todos_imported' => $service->importFromCsv($workspace, $content)],
            default => throw new \LogicException('Validated import format was not supported.'),
        };

        return response()->json(['imported' => $result]);
    }
}
