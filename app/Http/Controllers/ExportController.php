<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function export(Request $request, Workspace $workspace, string $format, ExportService $service): Response
    {
        $this->authorize('view', $workspace);

        return match ($format) {
            'json' => response($service->exportToJson($workspace))
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="workspace-export.json"'),
            'csv' => response($service->exportToCsv($workspace))
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="workspace-export.csv"'),
            'markdown' => response($service->exportToMarkdown($workspace))
                ->header('Content-Type', 'text/markdown')
                ->header('Content-Disposition', 'attachment; filename="workspace-export.md"'),
            default => abort(400, 'Unsupported format'),
        };
    }
}
