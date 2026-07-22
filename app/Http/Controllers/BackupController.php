<?php

namespace App\Http\Controllers;

use App\Actions\CreateDatabaseBackup;
use App\Actions\RestoreDatabaseBackup;
use App\Http\Requests\CreateDatabaseBackupRequest;
use App\Http\Requests\RestoreDatabaseBackupRequest;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function backup(
        CreateDatabaseBackupRequest $request,
        CreateDatabaseBackup $action,
    ): RedirectResponse {
        $request->validated();

        try {
            $action->handle();
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['backup' => __('ui.settings.backup.failed')]);
        }

        return back()->with('success', __('ui.settings.backup.created'));
    }

    public function list(BackupService $service): JsonResponse
    {
        Gate::authorize('manageDatabaseBackups');

        return response()->json(['backups' => $service->listBackups()]);
    }

    public function restore(
        RestoreDatabaseBackupRequest $request,
        string $backup,
        RestoreDatabaseBackup $action,
    ): RedirectResponse {
        $request->validated();

        try {
            $action->handle($backup);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['backup' => __('ui.settings.backup.restore_failed')]);
        }

        return to_route('dashboard')->with('success', __('ui.settings.backup.restored'));
    }

    public function download(string $backup, BackupService $service): BinaryFileResponse
    {
        Gate::authorize('manageDatabaseBackups');

        try {
            $path = $service->verifiedPath($backup);
        } catch (RuntimeException) {
            abort(404);
        }

        $response = response()->download(
            $path,
            'xiaomi-mimo-backup-'.now()->format('Ymd-His').'.sqlite',
            [
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
