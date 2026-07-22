<?php

namespace App\Actions;

use App\Services\BackupService;
use Illuminate\Contracts\Foundation\MaintenanceMode;

class RestoreDatabaseBackup
{
    public function __construct(
        private BackupService $backups,
        private MaintenanceMode $maintenanceMode,
    ) {}

    public function handle(string $id): void
    {
        $wasAlreadyActive = $this->maintenanceMode->active();

        if (! $wasAlreadyActive) {
            $this->maintenanceMode->activate([
                'except' => [],
                'redirect' => null,
                'retry' => (int) config('backup.maintenance_retry', 60),
                'refresh' => null,
                'secret' => null,
                'status' => 503,
                'template' => null,
            ]);
        }

        try {
            $this->backups->restore($id);
        } finally {
            if (! $wasAlreadyActive) {
                $this->maintenanceMode->deactivate();
            }
        }
    }
}
