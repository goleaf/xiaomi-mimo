<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function import(Request $request, Workspace $workspace, ImportService $service): JsonResponse
    {
        $this->authorize('update', $workspace);

        $request->validate([
            'file' => 'required|file|max:10240',
            'format' => 'required|string|in:json,csv',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());

        $result = match ($request->format) {
            'json' => $service->importFromJson($workspace, $content),
            'csv' => ['todos_imported' => $service->importFromCsv($workspace, $content)],
        };

        return response()->json(['imported' => $result]);
    }
}
