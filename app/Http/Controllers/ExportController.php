<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\ExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function export(
        Workspace $workspace,
        string $format,
        ExportService $service,
    ): StreamedResponse {
        $this->authorize('view', $workspace);

        [$filename, $contentType] = match ($format) {
            'json' => ['workspace-export.json', 'application/json; charset=UTF-8'],
            'csv' => ['workspace-export.csv', 'text/csv; charset=UTF-8'],
            'markdown' => ['workspace-export.md', 'text/markdown; charset=UTF-8'],
            default => abort(400, __('data_transfer.export.unsupported_format')),
        };

        return response()->streamDownload(
            fn () => $service->stream($workspace, $format),
            $filename,
            [
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
