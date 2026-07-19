<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function backup(BackupService $service): JsonResponse
    {
        $path = $service->backup();

        return response()->json(['backup' => basename($path), 'path' => $path]);
    }

    public function list(BackupService $service): JsonResponse
    {
        return response()->json(['backups' => $service->listBackups()]);
    }

    public function restore(string $filename, BackupService $service): JsonResponse
    {
        $path = storage_path("app/backups/{$filename}");

        if (! $service->restore($path)) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        return response()->json(['message' => 'Database restored successfully']);
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = storage_path("app/backups/{$filename}");

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path, $filename);
    }
}
