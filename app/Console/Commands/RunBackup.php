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
        $backup = $service->create();
        $this->info('Backup created: '.$backup['id']);

        return self::SUCCESS;
    }
}
