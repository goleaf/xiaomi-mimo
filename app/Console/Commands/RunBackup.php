<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Create a database backup';

    public function handle(BackupService $service): int
    {
        $path = $service->backup();
        $this->info("Backup created: " . basename($path));

        return self::SUCCESS;
    }
}
