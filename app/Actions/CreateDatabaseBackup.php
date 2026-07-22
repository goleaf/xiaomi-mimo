<?php

namespace App\Actions;

use App\Services\BackupService;

class CreateDatabaseBackup
{
    public function __construct(private BackupService $backups) {}

    /** @return array{id: string, size: int, created_at: int} */
    public function handle(): array
    {
        return $this->backups->create();
    }
}
